document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const errorDiv = document.getElementById('error');
    const successDiv = document.getElementById('success');
    const submitBtn = document.getElementById('submitBtn');

    const showError = (message) => {
        errorDiv.textContent = message;
        errorDiv.classList.remove('d-none');
    };

    const hideError = () => {
        errorDiv.textContent = '';
        errorDiv.classList.add('d-none');
    };

    const showSuccess = (message) => {
        successDiv.textContent = message;
        successDiv.classList.remove('d-none');
        errorDiv.classList.add('d-none');
    };

    const hideSuccess = () => {
        successDiv.textContent = '';
        successDiv.classList.add('d-none');
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();
        hideSuccess();
        errorDiv.textContent = '';

        const currentPass = document.getElementById('currentPass').value.trim();
        const pass1 = document.getElementById('pass1').value.trim();
        const pass2 = document.getElementById('pass2').value.trim();
        const csrfToken = document.getElementById('csrf_token').value;

        if (!currentPass || !pass1 || !pass2) {
            showError('Tous les champs sont obligatoires.');
            return;
        }

        if (pass1 !== pass2) {
            showError('Les mots de passe ne correspondent pas.');
            return;
        }

        // Désactivation du bouton
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enregistrement...';

        try {
            const response = await fetch(`/api/changePassword/${ID}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    currentPassword: currentPass,
                    newPassword: pass1
                })
            });

            const data = await response.json();

            if (!response.ok) {
                showError(data.message || 'Erreur lors du changement de mot de passe.');
                return;
            }

            hideError();
            showSuccess('🎉 Mot de passe modifié avec succès !');
            form.reset();

        } catch (err) {
            console.error(err);
            showError('Erreur serveur. Réessaie plus tard.');
        } finally {
            // Réactivation du bouton
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit';
        }
    });
});