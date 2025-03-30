# Technical Documentation: Career Path, Project Proposals & Collaboration Hub

This technical documentation describes the implementation of the career path, project proposals, and collaboration features in the VORTEX AI Marketplace plugin.

## Architecture Overview

The career, project, and collaboration features are implemented through these core components:

1. **Core Class**: `class-vortex-career-project-collaboration.php` - Central controller for all features
2. **Custom Post Types**: `vortex_project` and `vortex_collaboration` for storing project and collaboration data
3. **Custom Database Tables**: For managing collaboration requests and memberships
4. **Template Files**: Frontend interfaces for user interaction
5. **JavaScript**: Client-side functionality for form handling and UI interactions
6. **CSS**: Styling for all career, project, and collaboration interfaces
7. **AI Integration**: API connections to HURAII, CLOE, and Business Strategist agents

## Core Class Structure

The `Vortex_Career_Project_Collaboration` class follows a static factory pattern with these main methods:

### Initialization Methods
- `init()`: Bootstrap method that registers shortcodes, AJAX handlers, and scripts
- `register_shortcodes()`: Register the three main shortcodes
- `enqueue_scripts()`: Enqueue CSS and JavaScript files

### Rendering Methods
- `render_career_path()`: Renders career path interface
- `render_project_proposals()`: Renders project proposals interface
- `render_collaboration_hub()`: Renders collaboration hub interface

### AJAX Handlers
- `handle_career_path_submission()`: Process career path form submissions
- `handle_project_proposal_submission()`: Process project proposal submissions
- `handle_join_collaboration()`: Process collaboration join requests

### Utility Methods
- `get_user_collaborations()`: Retrieve user's active collaborations
- `get_ai_career_recommendations()`: Get AI-powered career recommendations
- `get_ai_team_suggestions()`: Get AI-powered team composition suggestions
- `get_fallback_career_recommendations()`: Generate basic recommendations if AI is unavailable

## Database Schema

### WordPress Post Types
```
vortex_project
├── Post ID
├── Post Title (project name)
├── Post Content (project description)
├── Post Author (project creator)
└── Post Meta
    ├── vortex_project_timeline
    ├── vortex_project_budget
    ├── vortex_skills_required (serialized array)
    └── vortex_project_status

vortex_collaboration
├── Post ID
├── Post Title (collaboration name)
├── Post Content (collaboration description)
├── Post Author (collaboration creator)
└── Post Meta
    ├── vortex_collaboration_type
    ├── vortex_collaboration_budget
    ├── vortex_collaboration_deadline
    ├── vortex_collaboration_requirements
    └── vortex_collaboration_roles (serialized array)
```

### Custom Tables
```
vortex_collaboration_requests
├── id (bigint, AUTO_INCREMENT, PRIMARY KEY)
├── collaboration_id (bigint) - References vortex_collaboration post ID
├── user_id (bigint) - References wp_users ID
├── request_date (datetime)
├── requested_role (varchar)
├── request_status (varchar) - pending, approved, rejected
├── request_message (text)
├── created_at (datetime)
└── updated_at (datetime)

vortex_collaboration_members
├── id (bigint, AUTO_INCREMENT, PRIMARY KEY)
├── collaboration_id (bigint) - References vortex_collaboration post ID
├── user_id (bigint) - References wp_users ID
├── role (varchar)
├── join_date (datetime)
├── status (varchar) - active, inactive
├── created_at (datetime)
└── updated_at (datetime)
```

## User Metadata
```
vortex_career_stage (string)
vortex_career_goals (text)
vortex_interests (serialized array)
vortex_user_skills (serialized array)
vortex_career_recommendations (serialized array)
```

## Shortcodes

| Shortcode | Handler Method | Template File |
|-----------|----------------|---------------|
| `[vortex_career_path]` | `render_career_path()` | `vortex-career-path.php` |
| `[vortex_project_proposals]` | `render_project_proposals()` | `vortex-project-proposals.php` |
| `[vortex_collaboration_hub]` | `render_collaboration_hub()` | `vortex-collaboration-hub.php` |

## AJAX Actions

| Action | Handler Method | Nonce Check |
|--------|---------------|-------------|
| `vortex_submit_career_path` | `handle_career_path_submission()` | `vortex_career_project_nonce` |
| `vortex_submit_project_proposal` | `handle_project_proposal_submission()` | `vortex_career_project_nonce` |
| `vortex_join_collaboration` | `handle_join_collaboration()` | `vortex_career_project_nonce` |
| `vortex_leave_collaboration` | `handle_leave_collaboration()` | `vortex_career_project_nonce` |

## Frontend Assets

### JavaScript Files
- `vortex-career-project.js`: Main JS file with three core modules:
  - `initCareerPathForm()`: Career path form submission handling
  - `initProjectProposals()`: Project proposals interface functionality
  - `initCollaborationHub()`: Collaboration hub interface functionality

### CSS Files
- `vortex-career-project.css`: Styles for all career, project, and collaboration interfaces

## Template Files

The template files use a consistent structure:
1. Headers with user authentication checks
2. Form components for data submission
3. Display components for existing data
4. Error/success message containers
5. Modal components for interactive dialogs

## AI Integration

### Business Strategist Integration
- Used primarily for career path recommendations
- Input: User career stage, goals, interests, and skills
- Output: Next steps, resources, and milestones
- Fallback: Basic recommendations if AI is unavailable

### CLOE Integration
- Used primarily for project team suggestions
- Input: Project details and required skills
- Output: Team composition suggestions
- Analysis: Skill gap detection and project viability assessment

### HURAII Integration
- Used for creative direction in collaborations
- Input: Collaboration type and goals
- Output: Creative inspiration and direction suggestions

## Security Considerations

1. **Authentication**: All features require user login
2. **Nonce Verification**: All AJAX handlers verify nonces
3. **Input Sanitization**: All user inputs are sanitized
4. **Permission Checks**: Appropriate capability checks for content editing
5. **Database Prepared Statements**: All database queries use prepared statements

## Error Handling

The system implements a tiered error handling approach:
1. **Client-Side Validation**: JavaScript form validation
2. **Server-Side Validation**: PHP validation in AJAX handlers
3. **Graceful Fallbacks**: Alternative processing if AI services are unavailable
4. **User Feedback**: Clear error messages for all failure scenarios

## Future Enhancements

Planned enhancements include:
1. **Direct Invitations**: Ability to invite specific users to collaborations
2. **Advanced Matching Algorithm**: Improved project-user matching
3. **Integration with TOLA**: Token rewards for successful collaborations
4. **Milestone Tracking**: Project milestone and progress tracking
5. **Enhanced Analytics**: Performance metrics for career progression

## Troubleshooting

Common issues and solutions:
1. **Database Table Creation**: If collaboration tables aren't created, manually trigger `Vortex_DB_Setup::init()`
2. **Missing AI Recommendations**: Check AI agent availability and API keys
3. **Form Submission Failures**: Verify AJAX URL configuration and nonce setup 