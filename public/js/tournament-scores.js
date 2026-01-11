// Tournament score input defaults
document.addEventListener('DOMContentLoaded', function () {
    // Set default values for tournament score inputs
    const scoreInputs = document.querySelectorAll('input[name="score1"], input[name="score2"]');
    scoreInputs.forEach(input => {
        if (!input.value || input.value === '') {
            input.value = '0';
        }
    });
});
