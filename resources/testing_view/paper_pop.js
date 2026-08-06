/**
 * Paper Pop Shared Engine JS
 */
document.addEventListener('DOMContentLoaded', () => {
  initToastRegion();
  initModals();
  initPasswordToggles();
  initOtpInputs();
  initPaperPopDrag();
});

/* 1. Toast Notification System */
function initToastRegion() {
  if (!document.getElementById('toast-region')) {
    const region = document.createElement('div');
    region.id = 'toast-region';
    region.setAttribute('aria-live', 'polite');
    document.body.appendChild(region);
  }
}

function showToast(text, undoLabel = null, onUndo = null, duration = 5000) {
  initToastRegion();
  const region = document.getElementById('toast-region');
  const toast = document.createElement('div');
  toast.className = 'toast';
  
  let html = `<span>${text}</span>`;
  if (undoLabel && onUndo) {
    html += `<button type="button" class="toast-undo-btn">${undoLabel}</button>`;
  }
  toast.innerHTML = html;

  if (undoLabel && onUndo) {
    const btn = toast.querySelector('.toast-undo-btn');
    btn.addEventListener('click', () => {
      onUndo();
      toast.remove();
    });
  }

  region.appendChild(toast);

  setTimeout(() => {
    if (toast.parentNode) {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      setTimeout(() => toast.remove(), 200);
    }
  }, duration);
}

/* 2. Modal & Bottom Sheet System */
let lastActiveElement = null;

function initModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeModal(overlay.id);
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const activeModal = document.querySelector('.modal-overlay.active');
      if (activeModal) {
        closeModal(activeModal.id);
      }
    }
  });
}

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  lastActiveElement = document.activeElement;
  modal.classList.add('active');
  modal.setAttribute('aria-hidden', 'false');
  const focusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
  if (focusable) focusable.focus();
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.remove('active');
  modal.setAttribute('aria-hidden', 'true');
  if (lastActiveElement) lastActiveElement.focus();
}

/* 3. Password Toggle Button */
function initPasswordToggles() {
  document.querySelectorAll('.password-toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        btn.setAttribute('aria-label', 'Ẩn mật khẩu');
        btn.innerHTML = '<i class="ph ph-eye-slash"></i>';
      } else {
        input.type = 'password';
        btn.setAttribute('aria-label', 'Hiện mật khẩu');
        btn.innerHTML = '<i class="ph ph-eye"></i>';
      }
    });
  });
}

/* 4. OTP Input Handler */
function initOtpInputs() {
  document.querySelectorAll('.otp-group').forEach(group => {
    const cells = group.querySelectorAll('.otp-cell');
    cells.forEach((cell, idx) => {
      cell.addEventListener('input', (e) => {
        const val = e.target.value;
        if (val.length === 1 && idx < cells.length - 1) {
          cells[idx + 1].focus();
        }
      });

      cell.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !cell.value && idx > 0) {
          cells[idx - 1].focus();
        }
      });

      cell.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').trim();
        if (/^\d{6}$/.test(pasted)) {
          pasted.split('').forEach((char, i) => {
            if (cells[i]) cells[i].value = char;
          });
          cells[5].focus();
        }
      });
    });
  });
}

/* 5. Paper Pop Drag Gesture Engine (Pointer Events) */
function initPaperPopDrag() {
  const cards = document.querySelectorAll('.note-paper--draggable');
  cards.forEach(card => attachPaperDrag(card));

  // Keyboard shortcut fallback (ArrowLeft / ArrowRight)
  document.addEventListener('keydown', (e) => {
    const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
    if (['input', 'textarea', 'select', 'button'].includes(tag)) return;

    const currentCard = document.querySelector('.current-note-paper');
    if (!currentCard) return;

    if (e.key === 'ArrowLeft') {
      triggerCardAction(currentCard, 'left', 'Đã chuyển thành Để sau');
    } else if (e.key === 'ArrowRight') {
      triggerCardAction(currentCard, 'right', 'Đã chuyển thành Hoàn thành');
    }
  });
}

function attachPaperDrag(card) {
  let isDragging = false;
  let startX = 0;
  let startY = 0;
  let currentX = 0;

  const badgeLeft = card.querySelector('.drag-indicator-badge--left');
  const badgeRight = card.querySelector('.drag-indicator-badge--right');

  card.addEventListener('pointerdown', (e) => {
    // Ignore drag on form fields, buttons, links, or pin menu
    if (e.target.closest('input, textarea, select, button, a, .pin-menu')) return;
    
    isDragging = true;
    startX = e.clientX;
    startY = e.clientY;
    card.style.transition = 'none';
    card.setPointerCapture(e.pointerId);
  });

  card.addEventListener('pointermove', (e) => {
    if (!isDragging) return;
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    // Lock horizontal swipe if vertical scroll is dominant
    if (Math.abs(dy) > Math.abs(dx) && Math.abs(dx) < 10) return;

    currentX = dx;
    const rot = Math.min(Math.max(dx * 0.08, -8), 8);
    card.style.transform = `translateX(${dx}px) rotate(${rot}deg)`;

    // Preview badges opacity
    if (dx < -32 && badgeLeft) {
      badgeLeft.style.opacity = Math.min((Math.abs(dx) - 32) / 60, 1);
      if (badgeRight) badgeRight.style.opacity = '0';
    } else if (dx > 32 && badgeRight) {
      badgeRight.style.opacity = Math.min((Math.abs(dx) - 32) / 60, 1);
      if (badgeLeft) badgeLeft.style.opacity = '0';
    } else {
      if (badgeLeft) badgeLeft.style.opacity = '0';
      if (badgeRight) badgeRight.style.opacity = '0';
    }
  });

  card.addEventListener('pointerup', (e) => {
    if (!isDragging) return;
    isDragging = false;

    if (badgeLeft) badgeLeft.style.opacity = '0';
    if (badgeRight) badgeRight.style.opacity = '0';

    if (currentX <= -92) {
      triggerCardAction(card, 'left', 'Đã chuyển thành Để sau');
    } else if (currentX >= 92) {
      triggerCardAction(card, 'right', 'Đã chuyển thành Hoàn thành');
    } else {
      // Spring back
      card.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1)';
      card.style.transform = 'none';
    }
  });

  card.addEventListener('pointercancel', () => {
    isDragging = false;
    if (badgeLeft) badgeLeft.style.opacity = '0';
    if (badgeRight) badgeRight.style.opacity = '0';
    card.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1)';
    card.style.transform = 'none';
  });
}

function triggerCardAction(card, direction, toastMessage) {
  const exitX = direction === 'left' ? -400 : 400;
  card.style.transition = 'transform 260ms ease, opacity 260ms ease';
  card.style.transform = `translateX(${exitX}px) rotate(${direction === 'left' ? -12 : 12}deg)`;
  card.style.opacity = '0';

  showToast(toastMessage, 'Undo', () => {
    card.style.transition = 'transform 200ms ease, opacity 200ms ease';
    card.style.transform = 'none';
    card.style.opacity = '1';
  });

  setTimeout(() => {
    // Reset card state for demo
    card.style.transition = 'none';
    card.style.transform = 'none';
    card.style.opacity = '1';
  }, 1000);
}
