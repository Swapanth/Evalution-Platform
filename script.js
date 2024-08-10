const teams = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta', 'Iota', 'Kappa'];
let finalSubmission = false;

function createTeamCard(team) {
    const card = document.createElement('div');
    card.className = 'team-card';
    card.innerHTML = `
        <h3>Team ${team}</h3>
        <input type="range" id="rating${team}" class="rating-slider" min="1" max="10" value="5">
        <div id="ratingValue${team}" class="rating-value">Rating: 5</div>
        <textarea id="comment${team}" placeholder="Share your insights on Team ${team}'s performance..."></textarea>
        <button id="submitBtn${team}">Submit Insight</button>
    `;
    return card;
}

function initializeTeamCards() {
    const container = document.getElementById('teamCardsContainer');
    teams.forEach(team => {
        container.appendChild(createTeamCard(team));
        initializeRatingSlider(team);
        initializeSubmitButton(team);
    });
}

function initializeRatingSlider(team) {
    const slider = document.getElementById(`rating${team}`);
    const ratingValue = document.getElementById(`ratingValue${team}`);
    
    slider.addEventListener('input', function() {
        ratingValue.textContent = `Rating: ${this.value}`;
    });
}

function initializeSubmitButton(team) {
    const submitButton = document.getElementById(`submitBtn${team}`);
    submitButton.addEventListener('click', () => submitTeamReview(team));
}

function submitTeamReview(team) {
    if (finalSubmission) {
        alert('Final submission has been made. Edits are no longer allowed.');
        return;
    }

    let rating = document.getElementById(`rating${team}`);
    let comment = document.getElementById(`comment${team}`);
    
    if (!rating.value || !comment.value.trim()) {
        alert('Please provide both a rating and a comment.');
        return;
    }

    // Here you would typically send the data to a server
    console.log(`Team ${team} Review - Rating: ${rating.value}, Comment: ${comment.value}`);

    // Disable inputs
    rating.disabled = true;
    comment.disabled = true;

    // Enable editing mode
    const card = document.querySelector(`.team-card:has(#rating${team})`);
    card.classList.add('submitted');
    
    const submitButton = card.querySelector('button');
    submitButton.textContent = 'Edit Review';
    submitButton.onclick = () => editTeamReview(team);

    checkAllCompleted();
}

function editTeamReview(team) {
    if (finalSubmission) {
        alert('Final submission has been made. Edits are no longer allowed.');
        return;
    }

    const rating = document.getElementById(`rating${team}`);
    const comment = document.getElementById(`comment${team}`);
    const card = document.querySelector(`.team-card:has(#rating${team})`);

    // Re-enable inputs
    rating.disabled = false;
    comment.disabled = false;

    card.classList.remove('submitted');
    card.classList.add('edit-mode');

    const submitButton = card.querySelector('button');
    submitButton.textContent = 'Submit Insight';
    submitButton.onclick = () => submitTeamReview(team);
}

function submitAllReviews() {
    const incompleteReviews = teams.filter(team => {
        const rating = document.getElementById(`rating${team}`);
        const comment = document.getElementById(`comment${team}`);
        return !rating.value || !comment.value.trim() || !rating.disabled;
    });

    if (incompleteReviews.length > 0) {
        alert(`Please complete reviews for: ${incompleteReviews.join(', ')}`);
    } else {
        finalSubmission = true;
        alert('All insights submitted successfully! Edits are no longer allowed.');
        disableAllInputs();
        showCompletionModal();
    }
}

function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const teamCards = document.querySelectorAll('.team-card');
        
        teamCards.forEach(card => {
            const teamName = card.querySelector('h3').textContent.toLowerCase();
            if (teamName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

function checkAllCompleted() {
    const allCompleted = teams.every(team => {
        const rating = document.getElementById(`rating${team}`);
        const comment = document.getElementById(`comment${team}`);
        return rating.disabled && comment.disabled;
    });

    if (allCompleted) {
        document.getElementById('submitAllBtn').classList.add('pulse');
    }
}

function showCompletionModal() {
    const modal = document.getElementById('completionModal');
    modal.style.display = 'block';

    const closeBtn = document.getElementById('closeModal');
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

function disableAllInputs() {
    teams.forEach(team => {
        document.getElementById(`rating${team}`).disabled = true;
        document.getElementById(`comment${team}`).disabled = true;
        document.querySelector(`.team-card:has(#rating${team}) button`).disabled = true;
    });
    document.getElementById('submitAllBtn').disabled = true;
}

// Initialize the page
initializeTeamCards();
setupSearch();

document.getElementById('submitAllBtn').addEventListener('click', submitAllReviews);