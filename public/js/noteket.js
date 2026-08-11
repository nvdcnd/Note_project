/* ============================================================
   Noteket — consolidated front-end JS
   Toast engine + inline card swap (EDIT/SHARE/REPLY/CREATE)
   + gesture drag engine
   ============================================================ */

(function () {
    'use strict';

    // --- Toast Notification Engine ---
    function showToast(message, isError = false) {
        const container = document.querySelector('.toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        const icon = document.createElement('i');
        icon.className = isError ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';
        const span = document.createElement('span');
        span.textContent = message;
        toast.appendChild(icon);
        toast.appendChild(span);
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 350);
        }, 2400);
    }
    window.showToast = showToast;

    // --- Flash messages rendered as toasts ---
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-toast]').forEach((el) => {
            const isError = el.dataset.toast === 'error';
            showToast(el.dataset.message, isError);
            el.remove();
        });
    });

    // --- Kiểu kéo thả theo chủ đề đang áp dụng ---
    // Chủ đề mang theo `drag_type`; layout đặt giá trị đó lên <body data-drag-type>.
    // 1 = Cổ điển, 2 = Xoay nhẹ, 3 = Bay. Giá trị lạ rơi về 1.
    const DRAG_PROFILES = {
        1: { rotateFactor: 0.04, scaleDivisor: 2000, minScale: 0.98, exitRotateFactor: 0.05 },
        2: { rotateFactor: 0.12, scaleDivisor: 1600, minScale: 0.96, exitRotateFactor: 0.15 },
        3: { rotateFactor: 0.02, scaleDivisor: 500, minScale: 0.88, exitRotateFactor: 0.03 },
    };

    function dragProfile() {
        const raw = document.body?.dataset?.dragType;
        return DRAG_PROFILES[raw] || DRAG_PROFILES[1];
    }

    // --- Helpers ---
    function validEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function copyToClipboard(text) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            showToast('📋 Đã sao chép liên kết!');
        }).catch(() => {
            showToast('⚠️ Sao chép liên kết thất bại', true);
        });
    }
    window.copyToClipboard = copyToClipboard;

    function playShakeAnimation(element) {
        element.style.transition = 'transform 0.2s';
        element.style.transform = 'none';
        element.classList.add('shake-animation');
        setTimeout(() => element.classList.remove('shake-animation'), 350);
    }

    function triggerCardExit(element, targetY, callback) {
        element.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
        element.style.transform = `translateY(${targetY}px) rotate(${targetY * dragProfile().exitRotateFactor}deg)`;
        element.style.opacity = '0';
        setTimeout(() => {
            if (typeof callback === 'function') callback();
            element.style.transition = 'none';
            element.style.transform = 'none';
            element.style.opacity = '1';
        }, 280);
    }

    // --- Inline Card Swap Engine ---
    function swapCardMode(card, mode, options = {}) {
        if (!card) return;

        if (!card.dataset.originalBodyHtml) {
            const bodyEl = card.querySelector('.card-body');
            if (bodyEl) card.dataset.originalBodyHtml = bodyEl.innerHTML;
            const headerTitleEl = card.querySelector('.note-header p');
            if (headerTitleEl) card.dataset.originalHeaderHtml = headerTitleEl.innerHTML;
        }

        const cardBody = card.querySelector('.card-body');
        const headerTitle = card.querySelector('.note-header p');
        if (!cardBody) return;

        card.style.transition = 'transform 0.22s ease, opacity 0.22s ease';
        card.style.transform = 'translateY(-120px) rotate(-6deg)';
        card.style.opacity = '0';

        setTimeout(() => {
            card.dataset.cardMode = mode;

            const noteId = options.noteId || card.dataset.noteId || '';

            if (mode === 'VIEW') {
                cardBody.innerHTML = card.dataset.originalBodyHtml;
                if (headerTitle && card.dataset.originalHeaderHtml) {
                    headerTitle.innerHTML = card.dataset.originalHeaderHtml;
                }
            } else if (mode === 'EDIT') {
                const currentTitle = card.querySelector('.card-title')?.textContent.trim() || '';
                // Ưu tiên nội dung đầy đủ (hidden span) thay vì bản đã cắt 200 ký tự trên card,
                // tránh ghi đè mất dữ liệu khi lưu từ chế độ sửa inline.
                const currentText = (card.querySelector('.note-full-description')?.textContent.trim()
                    || card.querySelector('.card-text')?.textContent.trim() || '');
                if (headerTitle) headerTitle.innerHTML = '✏️ Sửa ghi chú';
                cardBody.innerHTML = `
                    <form action="/edit/note/${noteId}" method="POST" onsubmit="return false;">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                        <div class="mb-2">
                            <label class="form-label" style="font-weight: bold;">Tiêu đề ghi chú</label>
                            <input type="text" class="form-control edit-title-input" name="title" value="${escapeHtml(currentTitle)}" required />
                        </div>
                        <div class="mb-2">
                            <label class="form-label" style="font-weight: bold;">Nội dung ghi chú</label>
                            <textarea class="form-control bigform edit-content-input" name="description" rows="3" required>${escapeHtml(currentText)}</textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary w-50 btn-cancel-swap">Hủy</button>
                            <button type="button" class="btn btn-primary w-50 btn-save-edit">Lưu thay đổi</button>
                        </div>
                    </form>`;
                cardBody.querySelector('.btn-cancel-swap')?.addEventListener('click', () => swapCardMode(card, 'VIEW', options));
                cardBody.querySelector('.btn-save-edit')?.addEventListener('click', () => {
                    const newTitle = cardBody.querySelector('.edit-title-input')?.value.trim();
                    const newContent = cardBody.querySelector('.edit-content-input')?.value.trim();
                    if (!newTitle && !newContent) {
                        showToast('⚠️ Tiêu đề hoặc nội dung không được để trống!', true);
                        playShakeAnimation(card);
                        return;
                    }
                    const form = cardBody.querySelector('form');
                    const token = form.querySelector('input[name="_token"]').value;
                    fetch(`/edit/note/${noteId}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: JSON.stringify({ title: newTitle, description: newContent }),
                    }).then(async (r) => {
                        if (r.redirected) { window.location.href = r.url; return null; }
                        // Không được coi mọi phản hồi là thành công: validation lỗi trả 422
                        // và trước đây vẫn hiện toast "Đã lưu thay đổi" dù không lưu được.
                        if (!r.ok) {
                            let message = 'Không thể lưu ghi chú';
                            try {
                                const data = await r.json();
                                if (data && data.errors) {
                                    message = Object.values(data.errors).flat().join(' ');
                                } else if (data && data.message) {
                                    message = data.message;
                                }
                            } catch (err) {
                                // Phản hồi không phải JSON — giữ thông báo mặc định.
                            }
                            throw new Error(message);
                        }
                        return r.json().catch(() => ({}));
                    }).then((data) => {
                        if (data === null) return; // đã chuyển hướng ở bước trên
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = card.dataset.originalBodyHtml;
                        const t = tempDiv.querySelector('.card-title');
                        const c = tempDiv.querySelector('.card-text');
                        if (t) t.textContent = newTitle;
                        if (c) c.textContent = newContent;
                        card.dataset.originalBodyHtml = tempDiv.innerHTML;
                        const fullDesc = card.querySelector('.note-full-description');
                        if (fullDesc) fullDesc.textContent = newContent;
                        showToast('🎉 Đã lưu thay đổi!');
                        swapCardMode(card, 'VIEW', options);
                    }).catch((err) => {
                        showToast('⚠️ '+((err && err.message) || 'Không thể lưu ghi chú'), true);
                        playShakeAnimation(card);
                    });
                });
            } else if (mode === 'SHARE') {
                if (headerTitle) headerTitle.innerHTML = '🔗 Chia sẻ ghi chú';
                const shareUrl = `${window.location.origin}/note/${noteId}`;
                let inlineEmails = [];
                cardBody.innerHTML = `
                    <div class="mb-2">
                        <label class="form-label" style="font-weight: bold;">Liên kết chia sẻ</label>
                        <div class="input-group mb-1">
                            <input type="text" readonly class="form-control share-url-input" value="${shareUrl}" />
                            <button class="btn btn-secondary btn-copy-share" type="button">Sao chép</button>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-weight: bold;">Chia sẻ qua email</label>
                        <div class="input-group mb-1">
                            <input type="email" class="form-control inline-email-input" placeholder="Nhập email..." />
                            <button class="btn btn-secondary btn-add-email" type="button">Thêm</button>
                        </div>
                        <div class="table-responsive style-scroll" style="max-height: 70px; overflow-y: auto;">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>#</th><th>Email</th></tr></thead>
                                <tbody class="inline-email-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary w-50 btn-cancel-swap">Quay lại</button>
                        <button type="button" class="btn btn-primary w-50 btn-submit-share">Chia sẻ</button>
                    </div>`;
                cardBody.querySelector('.btn-copy-share')?.addEventListener('click', () => copyToClipboard(shareUrl));
                const addEmailBtn = cardBody.querySelector('.btn-add-email');
                const emailInput = cardBody.querySelector('.inline-email-input');
                const tbody = cardBody.querySelector('.inline-email-tbody');
                addEmailBtn?.addEventListener('click', () => {
                    const em = emailInput?.value.trim();
                    if (validEmail(em)) {
                        if (inlineEmails.includes(em)) {
                            showToast('⚠️ Email đã có trong danh sách!', true);
                            return;
                        }
                        inlineEmails.push(em);
                        tbody.innerHTML = inlineEmails.map((e, idx) => `<tr><td>${idx + 1}</td><td>${escapeHtml(e)}</td></tr>`).join('');
                        emailInput.value = '';
                        showToast('✉️ Đã thêm email vào danh sách!');
                    } else {
                        showToast('⚠️ Vui lòng nhập email hợp lệ!', true);
                    }
                });
                cardBody.querySelector('.btn-cancel-swap')?.addEventListener('click', () => swapCardMode(card, 'VIEW', options));
                cardBody.querySelector('.btn-submit-share')?.addEventListener('click', () => {
                    if (inlineEmails.length === 0) {
                        showToast('⚠️ Danh sách chia sẻ trống!', true);
                        return;
                    }
                    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/share/note/${noteId}`;
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = '_token';
                    hidden.value = token;
                    form.appendChild(hidden);
                    inlineEmails.forEach((em) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'shared_with[]';
                        input.value = em;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                });
            } else if (mode === 'REPLY') {
                if (headerTitle) headerTitle.innerHTML = '💬 Trả lời ghi chú';
                cardBody.innerHTML = `
                    <form action="/reply/note/${noteId}" method="POST" onsubmit="return false;">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                        <div class="mb-2">
                            <label class="form-label" style="font-weight: bold;">Nội dung trả lời</label>
                            <textarea class="form-control bigform reply-text-input" name="description" rows="3" placeholder="Nhập nội dung trả lời..." required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary w-50 btn-cancel-swap">Hủy</button>
                            <button type="button" class="btn btn-primary w-50 btn-send-reply">Gửi trả lời</button>
                        </div>
                    </form>`;
                cardBody.querySelector('.btn-cancel-swap')?.addEventListener('click', () => swapCardMode(card, 'VIEW', options));
                cardBody.querySelector('.btn-send-reply')?.addEventListener('click', () => {
                    const replyText = cardBody.querySelector('.reply-text-input')?.value.trim();
                    if (!replyText) {
                        showToast('⚠️ Nội dung trả lời không được để trống!', true);
                        playShakeAnimation(card);
                        return;
                    }
                    cardBody.querySelector('form').submit();
                });
            } else if (mode === 'CREATE') {
                if (headerTitle) headerTitle.innerHTML = '✏️ Tạo ghi chú';
                const createUrl = options.organizationId ? `/create/note/organization/${options.organizationId}` : '/create/note';
                cardBody.innerHTML = `
                    <form action="${createUrl}" method="POST">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                        <div class="mb-2">
                            <label for="noteTitle-swap" class="form-label" style="font-weight: bold;">Tiêu đề ghi chú</label>
                            <input type="text" class="form-control" id="noteTitle-swap" name="title" placeholder="Tiêu đề..." required>
                        </div>
                        <div class="mb-2">
                            <label for="noteContent-swap" class="form-label" style="font-weight: bold;">Nội dung ghi chú</label>
                            <textarea class="form-control bigform" id="noteContent-swap" name="description" rows="3" placeholder="Viết nội dung..." required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary w-50 btn-cancel-swap">Hủy</button>
                            <button type="submit" class="btn btn-primary w-50">Tạo ghi chú</button>
                        </div>
                    </form>`;
                cardBody.querySelector('.btn-cancel-swap')?.addEventListener('click', () => {
                    swapCardMode(card, 'VIEW', options);
                    const fab = document.getElementById('fabBtn');
                    if (fab) fab.classList.remove('active-close');
                });
            }

            bindCardMenuEvents(card);

            card.style.transition = 'none';
            card.style.transform = 'translateY(80px) rotate(5deg)';
            requestAnimationFrame(() => {
                card.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s';
                card.style.transform = 'translateY(0) rotate(0deg)';
                card.style.opacity = '1';
            });
        }, 220);
    }
    window.swapCardMode = swapCardMode;

    // --- FAB Mobile Card Swap ---
    // Đúng demo: form tạo note ẩn hoàn toàn trên mobile, FAB flip card đầu tiên thành form tạo.
    window.toggleMobileCardSwap = function (options) {
        const mobileContainer = document.querySelector('.mobile-view .note-container');
        if (!mobileContainer) return;
        // Chỉ chọn card note đang hiển thị (bỏ qua card đã bị ẩn sau skip/hoàn thành).
        let card = [...mobileContainer.querySelectorAll('.note-card')].find((c) => c.offsetParent !== null) || null;
        const fab = document.getElementById('fabBtn');
        if (!card) {
            // Chưa có note nào: tạo tạm một card để chứa form tạo (khi bấm FAB lần nữa sẽ ẩn đi).
            card = document.createElement('div');
            card.className = 'card note-card';
            card.dataset.cardMode = 'VIEW';
            card.innerHTML =
                '<div class="card-header note-header" style="display: flex; justify-content: center;"><p>Ghi chú</p></div>' +
                '<div class="card-body rounded" style="background-color: #FFE86E; padding: 20px;">' +
                '<p class="text-muted text-center mb-0">Chưa có ghi chú. Bấm + để tạo ghi chú đầu tiên!</p>' +
                '</div>' +
                '<div class="note-overlay" aria-hidden="true"><div class="overlay-box">Tạo ghi chú</div></div>';
            mobileContainer.prepend(card);
            initNoteCard(card);
        }
        const currentMode = card.dataset.cardMode || 'VIEW';
        if (currentMode === 'VIEW') {
            if (fab) fab.classList.add('active-close');
            swapCardMode(card, 'CREATE', options || {});
        } else {
            if (fab) fab.classList.remove('active-close');
            swapCardMode(card, 'VIEW', options || {});
        }
    };

    function bindCardMenuEvents(card) {
        const pin = card.querySelector('.pin-btn');
        const menu = card.querySelector('.note-menu');
        if (pin && menu) {
            pin.onclick = (ev) => {
                ev.stopPropagation();
                menu.classList.toggle('show');
            };
        }
    }

    // --- Gesture Engine for Draggable Cards ---
    function initNoteCard(card) {
        if (!card) return;
        const menu = card.querySelector('.note-menu');
        const options = { noteId: card.dataset.noteId || '' };

        bindCardMenuEvents(card);

        document.addEventListener('click', (e) => {
            if (menu && !card.contains(e.target)) menu.classList.remove('show');
        });

        if (menu) {
            menu.addEventListener('click', (e) => {
                const targetBtn = e.target.closest('button');
                if (!targetBtn) return;
                const action = targetBtn.getAttribute('data-action') || targetBtn.textContent.trim().toLowerCase();
                e.stopPropagation();
                menu.classList.remove('show');

                if (action === 'delete') {
                    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const deleteUrl = `/delete/note/${options.noteId}`;
                    triggerCardExit(card, 380, () => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = deleteUrl;
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = '_token';
                        hidden.value = token;
                        const method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';
                        form.appendChild(hidden);
                        form.appendChild(method);
                        document.body.appendChild(form);
                        form.submit();
                    });
                } else if (action === 'edit') {
                    swapCardMode(card, 'EDIT', options);
                } else if (action === 'share') {
                    swapCardMode(card, 'SHARE', options);
                } else if (action === 'reply') {
                    swapCardMode(card, 'REPLY', options);
                } else if (action === 'mark-done') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/mark/note/${options.noteId}`;
                    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = '_token';
                    hidden.value = token;
                    form.appendChild(hidden);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        let isDragging = false;
        let startY = 0;
        let currentY = 0;

        function showOverlay(state) {
            const overlay = card.querySelector('.note-overlay');
            const overlayBox = overlay && overlay.querySelector('.overlay-box');
            if (!overlay || !overlayBox) return;
            const mode = card.dataset.cardMode || 'VIEW';

            if (state === 'down') {
                overlay.classList.add('show', 'down');
                overlay.classList.remove('up');
                if (mode === 'CREATE') overlayBox.textContent = '🧹 Thả để đặt lại biểu mẫu';
                else if (mode === 'EDIT') overlayBox.textContent = '🧹 Thả để hủy sửa';
                else if (mode === 'SHARE') overlayBox.textContent = '➔ Thả để quay lại';
                else if (mode === 'REPLY') overlayBox.textContent = '🧹 Thả để hủy trả lời';
                else overlayBox.textContent = '✓ Thả để đánh dấu hoàn thành';
            } else if (state === 'up') {
                overlay.classList.add('show', 'up');
                overlay.classList.remove('down');
                if (mode === 'CREATE') overlayBox.textContent = '➕ Thả để lưu ghi chú';
                else if (mode === 'EDIT') overlayBox.textContent = '➕ Thả để lưu thay đổi';
                else if (mode === 'SHARE') overlayBox.textContent = '🎉 Thả để chia sẻ ghi chú';
                else if (mode === 'REPLY') overlayBox.textContent = '💬 Thả để gửi trả lời';
                else overlayBox.textContent = '➔ Thả để bỏ qua ghi chú';
            } else {
                overlay.classList.remove('show', 'up', 'down');
            }
        }

        card.addEventListener('pointerdown', (e) => {
            if (e.button !== 0 && e.pointerType === 'mouse') return;
            if (e.target.closest('input, textarea, button, select, a, label, .pin-btn, .note-menu')) return;
            if (e.cancelable) e.preventDefault();
            isDragging = true;
            startY = e.clientY;
            currentY = 0;
            try { card.setPointerCapture(e.pointerId); } catch (err) {}
            card.style.transition = 'none';
            card.classList.add('dragging');
        });

        card.addEventListener('pointermove', (e) => {
            if (!isDragging) return;
            if (e.cancelable) e.preventDefault();
            currentY = e.clientY - startY;
            const profile = dragProfile();
            const rotate = currentY * profile.rotateFactor;
            const scale = Math.max(profile.minScale, 1 - Math.abs(currentY) / profile.scaleDivisor);
            card.style.transform = `translateY(${currentY}px) rotate(${rotate}deg) scale(${scale})`;
            if (currentY <= -35) showOverlay('up');
            else if (currentY >= 35) showOverlay('down');
            else showOverlay(null);
        });

        function handlePointerUpOrCancel(e) {
            if (!isDragging) return;
            isDragging = false;
            try { card.releasePointerCapture(e.pointerId); } catch (err) {}
            card.classList.remove('dragging');
            showOverlay(null);
            const mode = card.dataset.cardMode || 'VIEW';

            if (currentY < -120) {
                if (mode === 'CREATE') {
                    const form = card.querySelector('form');
                    const titleInput = form ? form.querySelector('[name="title"]') : null;
                    const contentInput = form ? form.querySelector('[name="description"]') : null;
                    const title = titleInput ? titleInput.value.trim() : '';
                    const content = contentInput ? contentInput.value.trim() : '';
                    if (!title && !content) {
                        showToast('⚠️ Tiêu đề hoặc nội dung không được để trống!', true);
                        playShakeAnimation(card);
                        card.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                        card.style.transform = 'none';
                    } else {
                        triggerCardExit(card, -380, () => {
                            showToast('🎉 Đã lưu ghi chú!');
                            if (form) form.submit();
                        });
                    }
                } else if (mode === 'EDIT') {
                    card.querySelector('.btn-save-edit')?.click();
                } else if (mode === 'SHARE') {
                    card.querySelector('.btn-submit-share')?.click();
                } else if (mode === 'REPLY') {
                    card.querySelector('.btn-send-reply')?.click();
                } else {
                    dismissNoteCard(card, '➔ Đã chuyển sang ghi chú tiếp theo', 'up');
                }
            } else if (currentY > 120) {
                if (mode !== 'VIEW') {
                    triggerCardExit(card, 380, () => {
                        swapCardMode(card, 'VIEW', options);
                        showToast('🧹 Đã hủy / đặt lại biểu mẫu');
                    });
                } else {
                    dismissNoteCard(card, '✓ Đã hoàn thành!', 'down');
                }
            } else {
                card.style.transition = 'transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.transform = 'none';
            }
            currentY = 0;
        }

        card.addEventListener('pointerup', handlePointerUpOrCancel);
        card.addEventListener('pointercancel', handlePointerUpOrCancel);
    }

    // --- Note deck helpers (xếp chồng kiểu bộ bài) ---
    function reflowDeck(deck) {
        if (!deck) return;
        const cards = deck.querySelectorAll('.note-card');
        const total = cards.length;
        cards.forEach((card, i) => {
            const offset = Math.min(i, 5) * 14;
            card.style.top = `${offset}px`;
            card.style.zIndex = String(total - i);
            const isTop = i === 0;
            card.classList.toggle('deck-inert', !isTop);
            card.inert = !isTop;
        });
        if (total === 0) {
            showToast('🎉 Đã xem hết ghi chú!');
            deck.style.display = 'none';
        } else {
            deck.style.display = '';
        }
    }

    // Dismiss (skip / hoàn thành) một card: bay ra ngoài rồi note kế tiếp trồi lên.
    function dismissNoteCard(card, toastMessage, direction) {
        const deck = card.closest('.note-deck');
        const targetY = direction === 'up' ? -380 : 380;
        triggerCardExit(card, targetY, () => {
            showToast(toastMessage);
            if (deck) {
                card.remove();
                reflowDeck(deck);
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Bỏ qua .note-card-static (card ở trang chi tiết ghi chú): nó chỉ để
        // hiển thị, không có menu ghim và không kéo thả được.
        document.querySelectorAll('.note-card:not(.note-card-static)').forEach(initNoteCard);
        document.querySelectorAll('.note-deck').forEach(reflowDeck);
    });

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }
})();
