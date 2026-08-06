# Home — Inbox / xử lý note

**Route/view:** `/` → `welcome`. **Dùng chung:** [base.md](base.md) toàn bộ, nhất là Paper note và gesture contract.

## 1. Mục tiêu và cây element

Màn này phải bám sketch: mobile xử lý **một** note tại tâm; desktop có hai tờ giấy đồng cấp để capture và xử lý song song.

```text
main.home-page
├─ header.context-bar
│  ├─ home icon/button (mobile; route Home)
│  ├─ select.status-filter (Done / Not done / All)
│  ├─ select.organization-filter (desktop/tablet; hoặc sheet mobile)
│  └─ avatar button → account drawer
├─ section.home-workspace
│  ├─ form.create-note-paper (desktop; hidden mobile)
│  │  ├─ paper header: “Create note”
│  │  ├─ title input + description textarea
│  │  └─ corner submit button
│  ├─ section.note-deck
│  │  ├─ article.current-note-paper
│  │  ├─ action row: Để sau / Hoàn thành
│  │  └─ text gesture hint
│  └─ aside.next-queue (wide desktop only)
├─ button.mobile-create-fab → create sheet (mobile)
└─ nav.bottom-nav (mobile) | aside.sidebar (tablet/desktop)
```

## 2. Element, thao tác và trạng thái

| Element | Con/hiển thị | Thao tác | Feedback/continuity |
| --- | --- | --- | --- |
| Status filter | label screen-reader, select `Not done` mặc định | đổi giá trị → refetch/filter deck, cập nhật URL query | skeleton chỉ trong deck; giữ organization filter và focus select |
| Organization filter | All/Cá nhân/org được phép | chọn scope → lọc note/create scope | mobile nằm trong filter sheet để topbar không vỡ; hiển thị scope text card |
| Create paper | title required, description required, corner submit | click corner/Enter submit; Escape/Hủy khi sheet | server success: clear form và đưa/mở note theo response; validation giữ draft/focus lỗi |
| Current note | pin menu, metadata, title, body, corner View | click body/corner → detail; drag/button do action | apply base E1; pin menu không bắt swipe |
| Pin menu | Edit, Done/Undo, Reply, Share, Delete theo quyền | tap pin → anchored menu; chọn action mở sheet/confirm | close outside/Esc; focus về pin; action unavailable không render |
| Queue | 3–5 next note links | click opens detail | desktop-only; không tự reorder khi drag current card |
| FAB | `+ Tạo note` accessible label | opens create bottom sheet | morph `+`→`×` 160ms; closing with dirty data confirms discard |

### Deck states

- **Loading:** skeleton paper đúng kích thước, disabled actions; không spinner giữa trang.
- **Empty:** title “Không có note phù hợp”, mô tả theo filter, CTA Tạo note hoặc Xem tất cả.
- **Error:** paper placeholder “Không thể tải note”, Retry; không xóa filter hiện tại.
- **Action pending:** disable card buttons/pin; giữ card tại chỗ đến response. Success theo gesture contract; request fail trả card vị trí cũ và toast Retry.

## 3. Motion cụ thể

| Element | Sự kiện | Animation bắt buộc |
| --- | --- | --- |
| create paper desktop | page enter | fade + translateY(12px), 220ms; không tự “bay” qua note |
| create sheet mobile | FAB click | overlay fade 160ms, sheet translateY 240ms; close đảo chiều |
| submit corner | hover/focus/click | scale 1.02 120ms; loading spinner, không xoay cả tờ giấy |
| current note drag | pointer move | translateX + rotate tối đa ±8deg, preview badge fade sau 32px |
| current note outcome | ≥92px release | exit theo hướng 260ms → next note fade/translate 200ms → Undo toast |
| below threshold/cancel | pointerup/cancel | spring transform về 0, 200ms; không mark done |
| pin menu | open/close | opacity + scale .96→1, 140ms; reduced motion opacity tức thì |

## 4. Responsive bắt buộc

| Size | Header/filter | Workspace | Navigation |
| --- | --- | --- | --- |
| 320–639 | home icon + status select + avatar; org filter trong sheet | chỉ current note full width; create thành bottom sheet/FAB; action row dưới note | bottom nav cố định |
| 640–767 | status + org select xếp/wrap, không icon-only | create sheet hoặc hai tờ dọc; queue dưới deck | icon rail |
| 768–1023 | hai filter ngang | 2 paper chỉ khi mỗi paper ≥300px, nếu không tạo sheet + note | icon rail, tooltip labels |
| ≥1024 | status + org filter trên top như sketch | create paper trái / current paper phải, gap 24px; queue chỉ khi main ≥1180px | sidebar label |

Không dùng `height: 100vh` làm note bị cắt; toàn trang có thể cuộn. Swipe luôn có two text buttons, kể cả desktop.
