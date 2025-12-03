// Main JavaScript file
console.log("main.js loaded successfully.");

function updateScore(scoreId, value) {
    console.log(`updateScore called for ${scoreId} with value ${value}`);
    const displayElement = document.getElementById(scoreId + '-display');
    const inputElement = document.getElementById(scoreId + '-input');
    
    if (!displayElement || !inputElement) {
        console.error(`Elements not found for scoreId: ${scoreId}`);
        return;
    }

    let currentScore = parseInt(displayElement.innerText, 10);
    console.log(`Current score for ${scoreId}: ${currentScore}`);
    currentScore += value;
    
    if (currentScore < 0) {
        currentScore = 0;
    }
    
    displayElement.innerText = currentScore;
    inputElement.value = currentScore;
    console.log(`New score for ${scoreId}: ${currentScore}`);
}