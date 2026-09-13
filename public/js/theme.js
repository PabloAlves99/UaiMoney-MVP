document.addEventListener('DOMContentLoaded', () => {

    const button = document.querySelector('#themeToggle');
    const csrfInput = document.querySelector('#csrfToken');

    if (!button || !csrfInput) {
        console.error(
            'Botão de tema ou token CSRF não encontrado.'
        );

        return;
    }


    function updateButton() {

        const currentTheme =
            document.documentElement.getAttribute(
                'data-bs-theme'
            ) || 'light';

        button.textContent =
            currentTheme === 'dark'
                ? '☀️ Tema claro'
                : '🌙 Tema escuro';
    }


    updateButton();


    button.addEventListener('click', async () => {

        const html = document.documentElement;

        const currentTheme =
            html.getAttribute(
                'data-bs-theme'
            ) || 'light';

        const newTheme =
            currentTheme === 'dark'
                ? 'light'
                : 'dark';


        /*
         * Altera visualmente primeiro.
         */

        html.setAttribute(
            'data-bs-theme',
            newTheme
        );

        updateButton();


        /*
         * Prepara envio para PHP.
         */

        const formData = new FormData();

        formData.append(
            'tema',
            newTheme
        );

        formData.append(
            '_token',
            csrfInput.value
        );


        try {

            const response = await fetch(
                window.UaiMoney.themeUrl,
                {
                    method: 'POST',
                    body: formData
                }
            );


            if (!response.ok) {

                const message =
                    await response.text();

                throw new Error(
                    message ||
                    'Não foi possível salvar o tema.'
                );
            }


            const result =
                await response.json();

            console.log(
                'Tema salvo:',
                result
            );


        } catch (error) {

            /*
             * Se falhar no backend,
             * desfaz a mudança visual.
             */

            html.setAttribute(
                'data-bs-theme',
                currentTheme
            );

            updateButton();

            console.error(
                'Erro ao alterar tema:',
                error
            );
        }

    });

});