document.addEventListener('DOMContentLoaded', function() {
    const sidebarItems = document.querySelectorAll('.sidebar ul li');
    const contents = document.querySelectorAll('.content');
    const dashboardCards = document.querySelectorAll('.card');

    sidebarItems.forEach((item) => {
        item.addEventListener('click', () => {
            sidebarItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            contents.forEach(content => content.classList.remove('active'));
            const contentId = item.id + '-content';
            document.getElementById(contentId).classList.add('active');
        });
    });

    dashboardCards.forEach(card => {
        card.addEventListener('click', () => {
            const target = card.getAttribute('data-target');
            sidebarItems.forEach(i => i.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            document.querySelector(`#${target}`).classList.add('active');
            document.querySelector(`#${target.replace('-content', '')}`).classList.add('active');
        });
    });

    // Dropdown for logout
    const userInfo = document.querySelector('.user-info');
    const dropdownContent = document.querySelector('.dropdown-content');
    const dropdownArrow = document.querySelector('.dropdown-arrow');

    dropdownArrow.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!userInfo.contains(e.target)) {
            dropdownContent.style.display = 'none';
        }
    });

    // Image preview for receipt upload
    const receiptInput = document.getElementById('receipt_image');
    const imagePreview = document.querySelector('.image-preview');

    receiptInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const imgElement = document.createElement('img');
                imgElement.setAttribute('src', event.target.result);
                imgElement.setAttribute('alt', 'Uploaded Receipt');
                imgElement.setAttribute('width', '200');

                if (imagePreview) {
                    imagePreview.innerHTML = '';  // Clear existing preview
                    imagePreview.appendChild(imgElement);
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Password visibility toggle for the change password form
    const showPasswordsCheckbox = document.getElementById('show_passwords');
    if (showPasswordsCheckbox) {
        showPasswordsCheckbox.addEventListener('change', function() {
            const currentPassword = document.getElementById('current_password');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');

            const passwordFields = [currentPassword, newPassword, confirmPassword];

            passwordFields.forEach(field => {
                field.type = this.checked ? 'text' : 'password';
            });
        });
    }
    
});
