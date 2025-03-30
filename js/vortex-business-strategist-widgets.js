/**
 * VORTEX Business Strategist Widgets
 * 
 * Handles the functionality of Business Strategist widgets
 * with modern interactions and theme integration.
 */

(function($) {
    'use strict';

    // Business Quiz Widget
    const BusinessQuizWidget = {
        init: function() {
            this.bindEvents();
            this.applyThemeColors();
        },

        bindEvents: function() {
            $('.vortex-quiz-option').on('click', this.handleOptionSelect.bind(this));
            $('#vortex-quiz-form').on('submit', this.handleQuizSubmit.bind(this));
        },

        handleOptionSelect: function(e) {
            const $option = $(e.currentTarget);
            const $question = $option.closest('.vortex-quiz-question');
            
            // Remove selected class from all options in this question
            $question.find('.vortex-quiz-option').removeClass('selected');
            
            // Add selected class to clicked option
            $option.addClass('selected');
        },

        handleQuizSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const $submitButton = $form.find('button[type="submit"]');
            const $errorMessage = $form.find('.vortex-error');
            const $successMessage = $form.find('.vortex-success');
            
            // Reset messages
            $errorMessage.text('').hide();
            $successMessage.text('').hide();
            
            // Show loading state
            $submitButton.prop('disabled', true).html('<span class="vortex-loading"></span>' + vortexBusinessStrategist.i18n.loading);
            
            // Collect answers
            const answers = {};
            $('.vortex-quiz-question').each(function() {
                const questionId = $(this).data('question-id');
                const selectedOption = $(this).find('.vortex-quiz-option.selected');
                answers[questionId] = selectedOption.length ? selectedOption.data('value') : null;
            });
            
            // Validate answers
            if (!this.validateAnswers(answers)) {
                this.handleError($submitButton, $errorMessage, 'Please answer all questions.');
                return;
            }
            
            // Submit quiz
            $.ajax({
                url: vortexBusinessStrategist.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_business_quiz_submit',
                    nonce: vortexBusinessStrategist.nonce,
                    answers: answers
                },
                success: (response) => {
                    if (response.success) {
                        this.handleSuccess($submitButton, $successMessage, response.data.message);
                        this.displayInsights(response.data.insights);
                    } else {
                        this.handleError($submitButton, $errorMessage, response.data);
                    }
                },
                error: () => {
                    this.handleError($submitButton, $errorMessage, vortexBusinessStrategist.i18n.error);
                }
            });
        },

        validateAnswers: function(answers) {
            return Object.values(answers).every(answer => answer !== null);
        },

        displayInsights: function(insights) {
            const $insightsContainer = $('.vortex-quiz-insights');
            if (!$insightsContainer.length) return;
            
            let insightsHtml = '<div class="vortex-insights-grid">';
            Object.entries(insights).forEach(([category, data]) => {
                insightsHtml += `
                    <div class="vortex-insight-card">
                        <h4>${category}</h4>
                        <p>${data.description}</p>
                        <ul>
                            ${data.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                `;
            });
            insightsHtml += '</div>';
            
            $insightsContainer.html(insightsHtml).slideDown();
        },

        handleSuccess: function($button, $message, text) {
            $button.prop('disabled', false).text(vortexBusinessStrategist.i18n.quiz_submit);
            $message.text(text).show();
        },

        handleError: function($button, $message, text) {
            $button.prop('disabled', false).text(vortexBusinessStrategist.i18n.quiz_submit);
            $message.text(text).show();
        },

        applyThemeColors: function() {
            const root = document.documentElement;
            Object.entries(vortexThemeColors).forEach(([key, value]) => {
                root.style.setProperty(`--vortex-${key}`, value);
            });
        }
    };

    // Business Plan Widget
    const BusinessPlanWidget = {
        init: function() {
            this.bindEvents();
            this.applyThemeColors();
        },

        bindEvents: function() {
            $('.vortex-plan-type').on('click', this.handlePlanTypeSelect.bind(this));
            $('#vortex-plan-form').on('submit', this.handlePlanGenerate.bind(this));
        },

        handlePlanTypeSelect: function(e) {
            const $planType = $(e.currentTarget);
            const $container = $planType.closest('.vortex-plan-type-selector');
            
            // Remove selected class from all plan types
            $container.find('.vortex-plan-type').removeClass('selected');
            
            // Add selected class to clicked plan type
            $planType.addClass('selected');
        },

        handlePlanGenerate: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const $submitButton = $form.find('button[type="submit"]');
            const $errorMessage = $form.find('.vortex-error');
            const $successMessage = $form.find('.vortex-success');
            
            // Reset messages
            $errorMessage.text('').hide();
            $successMessage.text('').hide();
            
            // Get selected plan type
            const selectedPlan = $('.vortex-plan-type.selected');
            if (!selectedPlan.length) {
                this.handleError($submitButton, $errorMessage, 'Please select a plan type.');
                return;
            }
            
            const planType = selectedPlan.data('type');
            
            // Show loading state
            $submitButton.prop('disabled', true).html('<span class="vortex-loading"></span>' + vortexBusinessStrategist.i18n.loading);
            
            // Generate plan
            $.ajax({
                url: vortexBusinessStrategist.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_business_plan_generate',
                    nonce: vortexBusinessStrategist.nonce,
                    plan_type: planType
                },
                success: (response) => {
                    if (response.success) {
                        this.handleSuccess($submitButton, $successMessage, response.data.message);
                        this.displayPlan(response.data.plan);
                    } else {
                        this.handleError($submitButton, $errorMessage, response.data);
                    }
                },
                error: () => {
                    this.handleError($submitButton, $errorMessage, vortexBusinessStrategist.i18n.error);
                }
            });
        },

        displayPlan: function(plan) {
            const $planContainer = $('.vortex-plan-content');
            if (!$planContainer.length) return;
            
            let planHtml = `
                <div class="vortex-plan-timeline">
                    ${plan.milestones.map(milestone => `
                        <div class="vortex-plan-milestone">
                            <div class="vortex-plan-milestone-header">
                                <h4>${milestone.title}</h4>
                                <span class="vortex-plan-milestone-date">Day ${milestone.day}</span>
                            </div>
                            <ul class="vortex-plan-milestone-tasks">
                                ${milestone.tasks.map(task => `<li>${task}</li>`).join('')}
                            </ul>
                        </div>
                    `).join('')}
                </div>
            `;
            
            $planContainer.html(planHtml).slideDown();
        },

        handleSuccess: function($button, $message, text) {
            $button.prop('disabled', false).text(vortexBusinessStrategist.i18n.plan_generate);
            $message.text(text).show();
        },

        handleError: function($button, $message, text) {
            $button.prop('disabled', false).text(vortexBusinessStrategist.i18n.plan_generate);
            $message.text(text).show();
        },

        applyThemeColors: function() {
            const root = document.documentElement;
            Object.entries(vortexThemeColors).forEach(([key, value]) => {
                root.style.setProperty(`--vortex-${key}`, value);
            });
        }
    };

    // Career Milestones Widget
    const CareerMilestonesWidget = {
        init: function() {
            this.bindEvents();
            this.applyThemeColors();
        },

        bindEvents: function() {
            $('.vortex-milestone-status').on('click', this.handleMilestoneUpdate.bind(this));
        },

        handleMilestoneUpdate: function(e) {
            const $status = $(e.currentTarget);
            const $milestone = $status.closest('.vortex-milestone');
            const milestoneId = $milestone.data('milestone-id');
            const currentStatus = $status.data('status');
            
            // Toggle status between pending and completed
            const newStatus = currentStatus === 'pending' ? 'completed' : 'pending';
            
            // Show loading state
            $status.addClass('loading');
            
            // Update milestone
            $.ajax({
                url: vortexBusinessStrategist.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_career_milestone_update',
                    nonce: vortexBusinessStrategist.nonce,
                    milestone_id: milestoneId,
                    status: newStatus
                },
                success: (response) => {
                    if (response.success) {
                        this.updateMilestoneStatus($status, newStatus);
                        this.updateProgressBar($milestone);
                    } else {
                        this.handleError($status, response.data);
                    }
                },
                error: () => {
                    this.handleError($status, vortexBusinessStrategist.i18n.error);
                }
            });
        },

        updateMilestoneStatus: function($status, newStatus) {
            $status
                .removeClass('loading pending completed')
                .addClass(newStatus)
                .data('status', newStatus);
        },

        updateProgressBar: function($milestone) {
            const $progressBar = $milestone.find('.vortex-milestone-progress-bar');
            const totalMilestones = $('.vortex-milestone').length;
            const completedMilestones = $('.vortex-milestone-status.completed').length;
            const progress = (completedMilestones / totalMilestones) * 100;
            
            $progressBar.css('width', `${progress}%`);
        },

        handleError: function($status, message) {
            $status.removeClass('loading');
            // You might want to show an error message here
        },

        applyThemeColors: function() {
            const root = document.documentElement;
            Object.entries(vortexThemeColors).forEach(([key, value]) => {
                root.style.setProperty(`--vortex-${key}`, value);
            });
        }
    };

    // Initialize widgets when document is ready
    $(document).ready(function() {
        BusinessQuizWidget.init();
        BusinessPlanWidget.init();
        CareerMilestonesWidget.init();
    });

})(jQuery); 