/**
 * Add feedback collection to chat interface
 */
function addFeedbackControls(messageElement, responseId) {
    const feedbackControls = document.createElement('div');
    feedbackControls.className = 'thorius-feedback-controls';
    
    // Create star rating
    const ratingContainer = document.createElement('div');
    ratingContainer.className = 'thorius-rating-container';
    
    const ratingLabel = document.createElement('span');
    ratingLabel.className = 'thorius-rating-label';
    ratingLabel.textContent = 'Rate response:';
    ratingContainer.appendChild(ratingLabel);
    
    const starRating = document.createElement('div');
    starRating.className = 'thorius-star-rating';
    
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.className = 'thorius-star';
        star.dataset.rating = i;
        star.innerHTML = '★';
        star.addEventListener('click', function() {
            // Remove active class from all stars
            this.parentNode.querySelectorAll('.thorius-star').forEach(s => {
                s.classList.remove('thorius-star-active');
            });
            
            // Add active class to selected star and all previous stars
            let current = this;
            while (current) {
                current.classList.add('thorius-star-active');
                current = current.previousElementSibling;
            }
            
            // Show feedback form if rating is 3 or lower
            const rating = parseInt(this.dataset.rating);
            const feedbackForm = messageElement.querySelector('.thorius-feedback-form');
            
            if (rating <= 3) {
                feedbackForm.style.display = 'block';
            } else {
                // For higher ratings, submit immediately
                submitFeedback(responseId, rating, '');
            }
        });
        starRating.appendChild(star);
    }
    
    ratingContainer.appendChild(starRating);
    feedbackControls.appendChild(ratingContainer);
    
    // Create feedback form (hidden by default)
    const feedbackForm = document.createElement('div');
    feedbackForm.className = 'thorius-feedback-form';
    feedbackForm.style.display = 'none';
    
    const feedbackTextarea = document.createElement('textarea');
    feedbackTextarea.className = 'thorius-feedback-textarea';
    feedbackTextarea.placeholder = 'Please tell us how we can improve this response...';
    feedbackForm.appendChild(feedbackTextarea);
    
    const feedbackSubmit = document.createElement('button');
    feedbackSubmit.className = 'thorius-feedback-submit';
    feedbackSubmit.textContent = 'Submit Feedback';
    feedbackSubmit.addEventListener('click', function() {
        const rating = messageElement.querySelector('.thorius-star-active:last-child').dataset.rating;
        const feedback = feedbackTextarea.value;
        submitFeedback(responseId, rating, feedback);
        
        // Hide feedback form
        feedbackForm.style.display = 'none';
        
        // Show thank you message
        const thankYou = document.createElement('div');
        thankYou.className = 'thorius-feedback-thank-you';
        thankYou.textContent = 'Thank you for your feedback!';
        feedbackControls.appendChild(thankYou);
        
        // Remove feedback controls after a delay
        setTimeout(() => {
            feedbackControls.style.opacity = '0';
            setTimeout(() => {
                feedbackControls.remove();
            }, 500);
        }, 3000);
    });
    feedbackForm.appendChild(feedbackSubmit);
    
    feedbackControls.appendChild(feedbackForm);
    messageElement.appendChild(feedbackControls);
}

/**
 * Submit feedback to the server
 */
function submitFeedback(responseId, rating, feedback) {
    const data = {
        action: 'vortex_thorius_feedback',
        nonce: thorius_data.feedback_nonce,
        interaction_id: responseId,
        rating: rating,
        feedback: feedback,
        agent: thorius_data.agent || 'thorius'
    };
    
    // Send feedback via AJAX
    fetch(thorius_data.ajax_url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Feedback submitted successfully');
        } else {
            console.error('Error submitting feedback:', data.message);
        }
    })
    .catch(error => {
        console.error('Error submitting feedback:', error);
    });
} 