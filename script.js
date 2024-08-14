const sheetId = "";
const apiKey = "";
const range = "teamnames!A2:A";

async function fetchTeamNames() {
  try {
    const url = `https://sheets.googleapis.com/v4/spreadsheets/${sheetId}/values/${range}?key=${apiKey}`;
    console.log("Fetching from URL:", url);
    
    const response = await fetch(url);
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error("Response not OK. Status:", response.status, "Text:", errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    teams = data.values ? data.values.map((row) => row[0]) : [];
    initializeTeamCards();
  } catch (error) {
    console.error("Error fetching team names:", error);
  }
}

function initializeTeamCards() {
  const container = document.getElementById("teamCardsContainer");
  container.innerHTML = ""; // Clear any existing cards
  teams.forEach((team) => {
      container.appendChild(createTeamCard(team));
      initializeRatingSlider(team);
      initializeSubmitButton(team);
  });
}

function initializeRatingSlider(team) {
  const slider = document.getElementById(`rating${team}`);
  const ratingValue = document.getElementById(`ratingValue${team}`);
  const ratingEmoji = ratingValue.querySelector('.rating-emoji');

  slider.addEventListener("input", function () {
      const value = this.value;
      ratingValue.firstChild.textContent = `${value} `;
      updateEmoji(value, ratingEmoji);
  });

  // Initialize the emoji based on the default value
  updateEmoji(slider.value, ratingEmoji);
}

function updateEmoji(value, emojiElement) {
  const emojis = ['😢', '😞', '😕', '😐', '🙂', '😊', '😄', '😃', '😍', '🤩'];
  emojiElement.textContent = emojis[value - 1];
}


function toggleTeamReview(team) {
    

    const rating = document.getElementById(`rating${team}`);
    const comment = document.getElementById(`comment${team}`);
    const card = rating.closest('.team-card');
    const submitButton = card.querySelector("button");

    if (card.classList.contains("submitted")) {
        // Switch to edit mode
        rating.disabled = false;
        comment.disabled = false;
        card.classList.remove("submitted");
        card.classList.add("edit-mode");
        submitButton.textContent = "Submit Insight";
    } else {
        // Submit the review
        if (!rating.value || !comment.value.trim()) {
            showToast("Please provide both a rating and a comment.", "warning");
            return;
        }

        rating.disabled = true;
        comment.disabled = true;
        card.classList.add("submitted");
        card.classList.remove("edit-mode");
        submitButton.textContent = "Edit Review";

        showToast(`Team ${team} review submitted successfully!`, "success");
        checkAllCompleted();
    }
}
function createTeamCard(team) {
  const card = document.createElement('div');
  card.className = 'team-card';
  card.innerHTML = `
      <h3>${team}</h3>
      <input type="range" id="rating${team}" class="rating-slider" min="1" max="10" value="5">
      <div class="rating-value" id="ratingValue${team}">5 <span class="rating-emoji">😐</span></div>
      <textarea id="comment${team}" placeholder="Share your insights..."></textarea>
      <button id="toggleBtn${team}">Submit</button>
  `;

  const slider = card.querySelector('.rating-slider');
  const ratingValue = card.querySelector('.rating-value');
  const ratingEmoji = ratingValue.querySelector('.rating-emoji');

  slider.addEventListener('input', () => {
      const value = slider.value;
      ratingValue.firstChild.textContent = `${value} `;
      updateEmoji(value, ratingEmoji);
  });

  return card;
}


function initializeSubmitButton(team) {
  const toggleButton = document.getElementById(`toggleBtn${team}`);
  toggleButton.addEventListener("click", () => toggleTeamReview(team));
}

// Function to get query parameter by name
function getQueryParam(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

async function sendDataToServer(data) {
  try {
    const response = await fetch("insert_reviews.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    });

    console.log("data:", data);
    const text = await response.text();
    console.log("Raw response:", text);

    const result = JSON.parse(text);
    if (result.status === "success") {
      showToast(result.message, "success");
    } else {
      showToast(result.message, "error");
    }
  } catch (error) {
  }
}

function submitAllReviews() {
    const incompleteReviews = teams.filter((team) => {
        const rating = document.getElementById(`rating${team}`);
        const comment = document.getElementById(`comment${team}`);
        const card = rating.closest('.team-card');
        return !card.classList.contains("submitted");
    });

    if (incompleteReviews.length > 0) {
        showToast(`Please complete reviews for: ${incompleteReviews.join(", ")}`, "warning");
    } else {
        finalSubmission = true;
        showToast("All insights submitted successfully! Edits are no longer allowed.", "success");
        disableAllInputs();
        showCompletionModal();

        const reviewData = teams.map((team) => {
            const ratingElement = document.getElementById(`rating${team}`);
            const commentElement = document.getElementById(`comment${team}`);
            return {
                team: team,
                rating: ratingElement ? ratingElement.value : "Not set",
                comment: commentElement ? commentElement.value : "Not provided",
                reviewed_by: getQueryParam("teamName") || "Unknown",
            };
        });

        sendDataToServer(reviewData);
    }
}

function logAllData() {
  const allData = teams.map((team) => {
    const ratingElement = document.getElementById(`rating${team}`);
    const commentElement = document.getElementById(`comment${team}`);
    return {
      team: team,
      rating: ratingElement ? ratingElement.value : "Not set",
      comment: commentElement ? commentElement.value : "Not provided",
      isComplete:
        ratingElement &&
        commentElement &&
        ratingElement.disabled &&
        commentElement.disabled,
    };
  });

  // Log all data regardless of completion status
  console.log("All data:", allData);

  // Format as a string for easier viewing
  const formattedData = allData
    .map(
      (item) =>
        `Team: ${item.team}\nRating: ${item.rating}\nComment: ${item.comment}\nComplete: ${item.isComplete}\n`
    )
    .join("\n");

  console.log("Formatted data:\n", formattedData);

  // Log team name from URL parameter
  const teamName = getQueryParam("teamName");
  if (teamName) {
    console.log(`Team Name: ${teamName}`);
  }
}

function showToast(message, type = "info") {
  let backgroundColor, textColor, icon;

  switch (type) {
    case "error":
      backgroundColor = "#FF5252";
      textColor = "#FFFFFF";
      icon = "❌";
      break;
    case "success":
      backgroundColor = "#4CAF50";
      textColor = "#FFFFFF";
      icon = "✅";
      break;
    case "warning":
      backgroundColor = "#FFC107";
      textColor = "#000000";
      icon = "⚠️";
      break;
    default:
      backgroundColor = "#2196F3";
      textColor = "#FFFFFF";
      icon = "ℹ️";
  }

  Toastify({
    text: `${icon} ${message}`,
    duration: 2000,
    close: true,
    gravity: "top",
    position: "right",
    backgroundColor: backgroundColor,
    stopOnFocus: true,
    onClick: function () {},
    style: {
      background: backgroundColor,
      color: textColor,
      boxShadow: "0 4px 6px rgba(0,0,0,0.1)",
      borderRadius: "9px",
      padding: "6px 10px",
      fontSize: "14px",
      fontWeight: "bold",
      maxWidth: "350px", // Constrain width to prevent multi-line
      whiteSpace: "nowrap", // Prevent text from wrapping
      overflow: "hidden", // Hide overflowed text
      textOverflow: "ellipsis", // Show ellipsis for overflowed text
      "@media (max-width: 600px)": {
        padding: "4px 8px",
        fontSize: "12px",
        borderRadius: "6px",
        maxWidth: "200px", // Adjust width for smaller screens
      },
      "@media (max-width: 400px)": {
        padding: "2px 6px",
        fontSize: "10px",
        borderRadius: "4px",
        maxWidth: "150px", // Further adjust width for very small screens
      },
    },
  }).showToast();
}

function setupSearch() {
  const searchInput = document.getElementById("searchInput");
  searchInput.addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    const teamCards = document.querySelectorAll(".team-card");

    teamCards.forEach((card) => {
      const teamName = card.querySelector("h3").textContent.toLowerCase();
      if (teamName.includes(searchTerm)) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  });
}

function checkAllCompleted() {
    const allCompleted = teams.every((team) => {
        const card = document.getElementById(`rating${team}`).closest('.team-card');
        return card.classList.contains("submitted");
    });

    if (allCompleted) {
        document.getElementById("submitAllBtn").classList.add("pulse");
    }
}

function showCompletionModal() {
  const modal = document.getElementById("completionModal");
  modal.style.display = "block";

  const closeBtn = document.getElementById("closeModal");
  closeBtn.onclick = function () {
    modal.style.display = "none";
  };

  window.onclick = function (event) {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  };
}

function disableAllInputs() {
    teams.forEach((team) => {
        const card = document.getElementById(`rating${team}`).closest('.team-card');
        card.querySelectorAll('input, textarea, button').forEach(el => el.disabled = true);
    });
    document.getElementById("submitAllBtn").disabled = true;
}

// Initialize the page
fetchTeamNames();
setupSearch();

document
  .getElementById("submitAllBtn")
  .addEventListener("click", submitAllReviews);
