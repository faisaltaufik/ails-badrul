<script>
    const toggleButton = document.querySelector('[data-password-toggle]');
    const passwordInput = document.getElementById('password');

    toggleButton?.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';
        toggleButton.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
    });
</script>