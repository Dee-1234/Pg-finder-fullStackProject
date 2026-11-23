</main>

    <footer class="bg-dark text-light py-4 mt-5">
    <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <span class="small text-secondary">PG Finder — Student Accommodation Portal</span>
        <span class="small text-secondary">Built with PHP, MySQL, AJAX, Bootstrap &amp; React</span>
    </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    async function handleAuthForm(formId, alertId) {
        const form = document.getElementById(formId);
        const alertBox = document.getElementById(alertId);
        if (!form || !alertBox) return;

        form.addEventListener('submit', async (e) => {
        e.preventDefault();
        alertBox.classList.add('d-none');
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
            method: 'POST',
            body: formData
            });
            const data = await response.json();

            if (data.success) {
            window.location.reload();
            } else {
            alertBox.textContent = data.message || 'Unable to process request.';
            alertBox.classList.remove('d-none');
            }
        } catch (err) {
            alertBox.textContent = 'Network error. Please try again.';
            alertBox.classList.remove('d-none');
        }
        });
    }

    handleAuthForm('loginForm', 'loginAlert');
    handleAuthForm('signupForm', 'signupAlert');
    </script>
</body>
</html>