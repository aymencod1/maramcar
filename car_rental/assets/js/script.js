// تحقق من صحة النماذج
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    const phoneInput = document.querySelector('input[name="phone"]');
    const phoneRegex = /^\+?[1-9]\d{1,14}$/;
    
    if (!phoneRegex.test(phoneInput.value)) {
        e.preventDefault();
        alert('يرجى إدخال رقم هاتف صحيح');
        phoneInput.focus();
        return false;
    }
    
    // تحقق إضافي للتاريخ
    const pickupDate = new Date(document.querySelector('input[name="pickup_date"]').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (pickupDate < today) {
        e.preventDefault();
        alert('لا يمكن اختيار تاريخ ماضي');
        return false;
    }
});

// تهيئة تاريخ الاختيار
flatpickr("input[type='date']", {
    minDate: "today",
    dateFormat: "Y-m-d",
    locale: "ar"
});

// تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // إضافة تأثيرات للصور
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.style.transition = 'transform 0.3s ease';
        img.addEventListener('mouseenter', () => {
            img.style.transform = 'scale(1.05)';
        });
        img.addEventListener('mouseleave', () => {
            img.style.transform = 'scale(1)';
        });
    });
});