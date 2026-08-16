document.addEventListener('DOMContentLoaded', () => {
    const dobInput = document.getElementById('dob');
    const form = document.getElementById('signupForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    dobInput.addEventListener('input', () => {
        let value = dobInput.value;
        if (value.length === 2 || value.length === 5) {
            dobInput.value = value + '/';
        }
    });

    form.addEventListener('submit', (event) => {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (password.length < 8) {
            alert('Password not strong enough');
            event.preventDefault();
            return;
        }

        if (password !== confirmPassword) {
            alert('Passwords do not match');
            event.preventDefault();
            return;
        }

        const agreeCheckbox = document.getElementById('agree');
        if (!agreeCheckbox.checked) {
            alert('You must agree to submit');
            event.preventDefault();
        }
    });
});
