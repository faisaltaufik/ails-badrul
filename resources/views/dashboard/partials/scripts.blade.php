<script>
    const meetingField = document.getElementById('sintak-pertemuan');
    const materialField = document.getElementById('sintak-materi');
    const sintakProjectInput = document.querySelector('[data-sintak-project-input]');
    const assistantInputs = document.querySelectorAll('input[name="assistant_picker"]');
    const assistantExtraContext = document.getElementById('assistant-extra-context');
    const openChatgptAssistantButton = document.getElementById('open-chatgpt-assistant');
    const assistantLaunchButtons = document.querySelectorAll('[data-assistant-launch]');
    const assistantLaunchFeedback = document.getElementById('assistant-launch-feedback');
    const materialOptions = @json(array_values($materialOptions ?? []));

    const getSelectedAssistantInput = () => document.querySelector('input[name="assistant_picker"]:checked');

    const getAssistantPromptText = () => assistantExtraContext?.value.trim() || '';

    const updateLaunchFeedback = (message) => {
        if (assistantLaunchFeedback) {
            assistantLaunchFeedback.hidden = false;
            assistantLaunchFeedback.textContent = message;
        }
    };

    const fallbackCopyText = (text) => {
        const helper = document.createElement('textarea');

        helper.value = text;
        helper.setAttribute('readonly', 'readonly');
        helper.style.position = 'fixed';
        helper.style.top = '-9999px';
        helper.style.opacity = '0';

        document.body.appendChild(helper);
        helper.focus();
        helper.select();
        helper.setSelectionRange(0, helper.value.length);

        let copied = false;

        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        document.body.removeChild(helper);

        return copied;
    };

    const materialForMeeting = (meetingValue) => {
        const meetingNumber = Number.parseInt(`${meetingValue || ''}`, 10);

        if (! Number.isFinite(meetingNumber) || materialOptions.length === 0) {
            return materialField?.value || '';
        }

        const index = Math.min(Math.max(meetingNumber - 1, 0), materialOptions.length - 1);

        return materialOptions[index] || materialOptions[0] || '';
    };

    const meetingForMaterial = (materialValue) => {
        const index = materialOptions.findIndex((option) => option === materialValue);

        if (index === -1) {
            return meetingField?.value || '';
        }

        return `${index + 1}`;
    };

    const syncMaterialField = () => {
        if (! materialField || ! meetingField) {
            return;
        }

        if (sintakProjectInput) {
            const selectedProjectId = `${meetingField.value || materialField.value || sintakProjectInput.value || ''}`;

            if (selectedProjectId === '') {
                return;
            }

            meetingField.value = selectedProjectId;
            materialField.value = selectedProjectId;
            sintakProjectInput.value = selectedProjectId;

            return;
        }

        materialField.value = materialForMeeting(meetingField.value);
    };

    const syncAssistantSelectionState = () => {
        assistantInputs.forEach((input) => {
            input.closest('.assistant-option')?.classList.toggle('is-selected', input.checked);
        });
    };

    const syncAssistantLaunchState = () => {
        if (! openChatgptAssistantButton) {
            return;
        }

        openChatgptAssistantButton.disabled = assistantInputs.length === 0 || getAssistantPromptText() === '';
    };

    const launchPromptText = async (promptText, emptyMessage) => {
        if (! promptText) {
            updateLaunchFeedback(emptyMessage);
            syncAssistantLaunchState();
            return;
        }

        await copyPrompt(promptText);
        const openedWindow = window.open('https://chatgpt.com/', '_blank');

        openedWindow?.focus();
    };

    const syncAssistantExtraContext = () => {
        if (! assistantExtraContext) {
            return;
        }

        assistantExtraContext.value = getSelectedAssistantInput()?.dataset.defaultExtraContext || '';
    };

    const copyPrompt = async (text) => {
        if (! text.trim()) {
            updateLaunchFeedback('Prompt AI masih kosong. Pilih assistant terlebih dahulu.');
            return false;
        }

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
            } else if (! fallbackCopyText(text)) {
                throw new Error('Clipboard API tidak tersedia.');
            }

            return true;
        } catch (error) {
            if (fallbackCopyText(text)) {
                return true;
            }

            updateLaunchFeedback('Clipboard gagal diakses. ChatGPT Web tetap dibuka, tetapi prompt perlu disalin manual.');
            return false;
        }
    };

    const openChatgptAssistant = async () => {
        const promptText = getAssistantPromptText();

        await launchPromptText(promptText, 'Isi prompt pada kolom Tambahkan Prompt terlebih dahulu.');

        // updateLaunchFeedback(
        //     copied
        //         ? 'ChatGPT Web dibuka di tab baru. Prompt sudah disalin ke clipboard.'
        //         : 'ChatGPT Web dibuka di tab baru. Salin prompt manual dari area assistant lalu tempel di ChatGPT.'
        // );
    };

    assistantInputs.forEach((input) => {
        input.addEventListener('change', () => {
            syncAssistantExtraContext();
            syncAssistantSelectionState();
            syncAssistantLaunchState();
        });
    });

    document.querySelectorAll('[data-delete-confirm-button]').forEach((button) => {
        button.addEventListener('click', () => {
            const confirmMessage = button.getAttribute('data-confirm-message')?.trim() || 'Apakah Anda yakin ingin menghapus data ini?';

            if (! window.confirm(confirmMessage)) {
                return;
            }

            button.form?.submit();
        });
    });

    assistantExtraContext?.addEventListener('input', () => {
        syncAssistantLaunchState();
        updateLaunchFeedback('Prompt akan disalin ke clipboard lalu ChatGPT Web dibuka.');
    });

    if (meetingField && ! meetingField.hasAttribute('data-sintak-autosubmit')) {
        meetingField.addEventListener('change', () => {
            syncMaterialField();
        });

        materialField?.addEventListener('change', () => {
            meetingField.value = meetingForMaterial(materialField.value);
        });
    }

    document.querySelectorAll('[data-sintak-autosubmit]').forEach((input) => {
        input.addEventListener('change', () => {
            if (sintakProjectInput) {
                const selectedProjectId = `${input.value || sintakProjectInput.value || ''}`;

                if (selectedProjectId !== '') {
                    meetingField.value = selectedProjectId;
                    materialField.value = selectedProjectId;
                    sintakProjectInput.value = selectedProjectId;
                }

                input.form?.requestSubmit();

                return;
            }

            if (input === meetingField) {
                syncMaterialField();
            }

            if (input === materialField && meetingField) {
                meetingField.value = meetingForMaterial(materialField.value);
            }

            input.form?.requestSubmit();
        });
    });

    openChatgptAssistantButton?.addEventListener('click', async () => {
        await openChatgptAssistant();
    });

    assistantLaunchButtons.forEach((button) => {
        if (button === openChatgptAssistantButton) {
            return;
        }

        button.addEventListener('click', async () => {
            const promptText = button.dataset.assistantPrompt?.trim() || '';

            await launchPromptText(promptText, 'Prompt AI refleksi belum tersedia untuk proyek ini.');
        });
    });

    syncMaterialField();
    syncAssistantExtraContext();
    syncAssistantSelectionState();
    syncAssistantLaunchState();
</script>