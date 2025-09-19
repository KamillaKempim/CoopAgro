// Funções JavaScript para melhorar a experiência do usuário
document.addEventListener('DOMContentLoaded', function() {
    // Exibir mensagens de sucesso
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const successCode = urlParams.get('success');
        let message = '';
        
        switch(successCode) {
            case '1':
                message = 'Produto adicionado com sucesso!';
                break;
            case '2':
                message = 'Produto atualizado com sucesso!';
                break;
            case '3':
                message = 'Produto excluído com sucesso!';
                break;
        }
        
        if (message) {
            alert(message);
            // Remover o parâmetro da URL sem recarregar a página
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }
    
    // Validação de formulários
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const numberInputs = this.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                if (input.hasAttribute('min') && parseFloat(input.value) < parseFloat(input.getAttribute('min'))) {
                    e.preventDefault();
                    alert(`O valor de ${input.previousElementSibling.textContent} não pode ser menor que ${input.getAttribute('min')}`);
                    input.focus();
                }
            });
        });
    });
});