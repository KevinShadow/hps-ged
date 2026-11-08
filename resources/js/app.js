import 'bootstrap';
import { Modal } from 'bootstrap';

const confirmationModalElement = document.querySelector('[data-action-confirmation-modal]');

if (confirmationModalElement) {
    const confirmationModal = Modal.getOrCreateInstance(confirmationModalElement);
    const confirmationTitle = confirmationModalElement.querySelector('[data-confirm-title]');
    const confirmationMessage = confirmationModalElement.querySelector('[data-confirm-message]');
    const confirmationSubmit = confirmationModalElement.querySelector('[data-confirm-submit]');
    let pendingForm = null;
    let pendingSubmitter = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            pendingForm = form;
            pendingSubmitter = event.submitter;
            confirmationTitle.textContent = form.dataset.confirmTitle ?? 'Confirmer l’action';
            confirmationMessage.textContent = form.dataset.confirmMessage ?? 'Confirmez-vous cette action ?';
            confirmationSubmit.textContent = form.dataset.confirmButton ?? 'Confirmer';
            const variant = ['danger', 'primary', 'success'].includes(form.dataset.confirmVariant)
                ? form.dataset.confirmVariant
                : 'danger';

            confirmationSubmit.classList.remove('btn-danger', 'btn-primary', 'btn-success');
            confirmationSubmit.classList.add(`btn-${variant}`);
            confirmationModal.show();
        });
    });

    confirmationSubmit.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        const form = pendingForm;
        const submitter = pendingSubmitter;
        pendingForm = null;
        pendingSubmitter = null;
        form.dataset.confirmed = 'true';

        if (submitter) {
            form.requestSubmit(submitter);
        } else {
            form.requestSubmit();
        }

        confirmationModal.hide();
    });

    confirmationModalElement.addEventListener('hidden.bs.modal', () => {
        pendingForm = null;
        pendingSubmitter = null;
    });
}

document.querySelectorAll('[data-document-upload]').forEach((form) => {
    const fileInputs = Array.from(form.querySelectorAll('[data-upload-input]'));
    const selection = form.querySelector('[data-upload-selection]');
    const modalElement = form.parentElement.querySelector('[data-upload-modal]');
    const uploadModal = Modal.getOrCreateInstance(modalElement);
    const modalTitle = modalElement.querySelector('[data-upload-modal-title]');
    const modalMessage = modalElement.querySelector('[data-upload-message]');
    const addedWrapper = modalElement.querySelector('[data-upload-added-wrapper]');
    const added = modalElement.querySelector('[data-upload-added]');
    const duplicatesWrapper = modalElement.querySelector('[data-upload-duplicates-wrapper]');
    const duplicates = modalElement.querySelector('[data-upload-duplicates]');
    const errorsWrapper = modalElement.querySelector('[data-upload-errors-wrapper]');
    const errors = modalElement.querySelector('[data-upload-errors]');
    const progressLabel = form.querySelector('[data-upload-progress-label]');
    const progress = form.querySelector('[data-upload-progress]');
    const progressBar = form.querySelector('[data-upload-progress-bar]');
    const submitButton = form.querySelector('[data-upload-submit]');
    const refreshButton = form.querySelector('[data-upload-refresh]');

    const showModal = ({ title, message, addedNames = [], duplicateNames = [], uploadErrors = [], canRefresh = false }) => {
        modalTitle.textContent = title;
        modalMessage.textContent = message;

        added.replaceChildren(...addedNames.map((name) => {
            const item = document.createElement('li');
            item.textContent = name;
            return item;
        }));
        addedWrapper.classList.toggle('d-none', addedNames.length === 0);

        duplicates.replaceChildren(...duplicateNames.map((name) => {
            const item = document.createElement('li');
            item.textContent = name;
            return item;
        }));
        duplicatesWrapper.classList.toggle('d-none', duplicateNames.length === 0);

        errors.replaceChildren(...uploadErrors.map((error) => {
            const item = document.createElement('li');
            item.textContent = error;
            return item;
        }));
        errorsWrapper.classList.toggle('d-none', uploadErrors.length === 0);
        refreshButton.classList.toggle('d-none', !canRefresh);
        uploadModal.show();
    };

    if (modalElement.dataset.uploadResult === 'true') {
        uploadModal.show();
    }

    const selectedFiles = () => fileInputs.flatMap((input) =>
        Array.from(input.files ?? []).map((file) => ({
            file,
            relativePath: file.webkitRelativePath || file.name,
        })),
    );

    fileInputs.forEach((input) => {
        input.addEventListener('change', () => {
            const count = selectedFiles().length;
            selection.textContent = count === 0
                ? 'Aucun fichier sélectionné.'
                : `${count} fichier(s) sélectionné(s).`;
        });
    });

    refreshButton.addEventListener('click', () => window.location.reload());

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const uploads = selectedFiles();

        if (uploads.length === 0) {
            showModal({
                title: 'Aucun fichier sélectionné',
                message: 'Sélectionnez un ou plusieurs fichiers, ou un dossier contenant des fichiers autorisés.',
            });
            return;
        }

        progress.classList.remove('d-none');
        progressLabel.classList.remove('d-none');
        submitButton.disabled = true;
        fileInputs.forEach((input) => {
            input.disabled = true;
        });

        let uploadedCount = 0;
        let submissionReopened = false;
        const addedNames = [];
        const duplicateNames = [];
        const uploadErrors = [];

        for (const [index, upload] of uploads.entries()) {
            progressLabel.textContent = `Envoi du fichier ${index + 1} sur ${uploads.length}…`;
            const progressPercentage = Math.round((index / uploads.length) * 100);
            progressBar.style.width = `${progressPercentage}%`;
            progressBar.setAttribute('aria-valuenow', String(progressPercentage));

            const body = new FormData();
            body.append('_token', form.querySelector('input[name="_token"]').value);
            body.append('monthly_submission_id', form.querySelector('[name="monthly_submission_id"]').value);
            body.append('files[0]', upload.file, upload.file.name);
            body.append('relative_paths[0]', upload.relativePath);

            let response;

            try {
                response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
            } catch {
                uploadErrors.push(`${upload.relativePath}: erreur de connexion pendant l’envoi.`);
                break;
            }

            const contentType = response.headers.get('content-type') ?? '';
            let result = null;

            if (contentType.includes('application/json')) {
                try {
                    result = await response.json();
                } catch {
                    result = null;
                }
            }

            if (!response.ok) {
                const validationErrors = Object.values(result?.errors ?? {}).flat();
                const fallbackMessage = result?.message
                    ?? `Le serveur a répondu avec le statut ${response.status}.`;
                uploadErrors.push(`${upload.relativePath}: ${validationErrors.join(' ') || fallbackMessage}`);
                continue;
            }

            if (!Number.isInteger(result?.uploaded)) {
                uploadErrors.push(`${upload.relativePath}: réponse invalide du serveur.`);
                continue;
            }

            uploadedCount += result.uploaded;
            submissionReopened ||= result.reopened === true;
            addedNames.push(...(Array.isArray(result.added) ? result.added : []));
            duplicateNames.push(...(Array.isArray(result.duplicates) ? result.duplicates : []));
        }

        progressBar.style.width = '100%';
        progressBar.setAttribute('aria-valuenow', '100');
        progress.classList.add('d-none');
        progressLabel.classList.add('d-none');
        progressLabel.textContent = '';
        submitButton.disabled = false;
        fileInputs.forEach((input) => {
            input.disabled = false;
            input.value = '';
        });

        const uploadedMessage = uploadedCount === 0
            ? 'Aucun nouveau document n’a été ajouté.'
            : `${uploadedCount} document${uploadedCount > 1 ? 's' : ''} ajouté${uploadedCount > 1 ? 's' : ''} avec succès.`;
        const duplicateMessage = duplicateNames.length > 0
            ? ` ${duplicateNames.length} doublon${duplicateNames.length > 1 ? 's' : ''} ignoré${duplicateNames.length > 1 ? 's' : ''} : un fichier portant le même nom existe déjà dans ce dossier mensuel.`
            : '';
        const errorMessage = uploadErrors.length > 0
            ? ` ${uploadErrors.length} fichier${uploadErrors.length > 1 ? 's' : ''} n’a${uploadErrors.length > 1 ? 'ont' : ''} pas pu être envoyé${uploadErrors.length > 1 ? 's' : ''}.`
            : '';
        const reopenedMessage = submissionReopened
            ? ' Le dossier a été rouvert en brouillon ; actualisez la page avant de le retransmettre.'
            : '';
        const title = uploadErrors.length > 0
            ? 'Envoi terminé avec des erreurs'
            : duplicateNames.length > 0
                ? (uploadedCount > 0 ? 'Envoi terminé : doublons ignorés' : 'Ces fichiers existent déjà')
                : uploadedCount > 0 ? 'Documents ajoutés' : 'Aucun document ajouté';

        showModal({
            title,
            message: `${uploadedMessage}${duplicateMessage}${errorMessage}${reopenedMessage}`,
            addedNames,
            duplicateNames,
            uploadErrors,
            canRefresh: uploadedCount > 0 || submissionReopened,
        });

        if (uploadedCount > 0 || duplicateNames.length > 0) {
            selection.textContent = uploadedCount > 0
                ? `${uploadedCount} document${uploadedCount > 1 ? 's' : ''} ajouté${uploadedCount > 1 ? 's' : ''}.`
                : 'Les doublons ont été ignorés.';
        } else {
            selection.textContent = 'Aucun document envoyé.';
        }

        refreshButton.textContent = submissionReopened
            ? 'Actualiser pour retransmettre le dossier'
            : 'Actualiser les documents';
    });
});
