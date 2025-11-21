$(document).ready(function() {
    $('#product-filter-select2').select2({
        placeholder: '-- Lọc theo sản phẩm --',
        allowClear: true,
        // Để Select2 không bị ảnh hưởng bởi Tailwind CSS
        width: 'style', 
        containerCssClass: 'custom-select2-container', // Thêm class tùy chỉnh nếu cần thêm style
        selectionCssClass: 'custom-select2-selection',
        dropdownCssClass: 'custom-select2-dropdown'
    });

    // === Xử lý sự kiện khi nhấn nút Xóa ===
    $('.btn-delete-faq').on('click', function(e) {
        e.preventDefault();
        var faqId = $(this).data('id');

        Swal.fire({
            title: 'Xác nhận xóa câu hỏi',
            text: 'Bạn có chắc chắn muốn xóa câu hỏi này? Hành động này sẽ xóa cả câu trả lời liên quan và không thể hoàn tác.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Có, xóa!',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?module=faqs&action=delete&id=' + faqId;
            }
        });
    });
});