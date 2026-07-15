# Dixeo Course Designer

The **Dixeo Course Designer** is an AI-powered Moodle block that accelerates the definition and generation of Moodle course structure, resources and activities from user-provided source documents and following a selected pedagogical design template.

# Features

- AI-assisted course design workflow
- Generation of course structure
- Generation of sections, resources and activities
- Interactive course design interface
- Drag-and-drop course organisation
- Inline editing and refinement
- Undo and revision support
- Progress tracking during generation
- Pedagogical design template support
- Optional certificate integration

# Requirements

- **Moodle:** 4.3 or later  
- **Dependency:** `local_dixeo` 2026051500 or later and a valid Dixeo API key  
- **Automatic certificate configuration** (optional): `mod_coursecertificate` and `tool_certificate`

# Installation

1. Copy `dixeo_designer` to `/blocks/dixeo_designer/`
2. Visit Site Administration > Notifications
3. Complete the Moodle upgrade.
4. Verify that **[Dixeo AI](../../../moodle-local_dixeo)** has been configured with a valid Dixeo API key.

# Configuration

The plugin provides several administrator settings.
- **Default Course Category** : Specify the Moodle category where newly generated courses will be created.
- **Course Template** : Set the default pedagogical template.
- **Certificate Generation** : Configure automatic certificate generation
  - Enable/disable certificate generation on course generation
  - Selecting the certificate template to use
  - Select where to place the certificate : Section 0 or new section after last

# User Roles

The plugin is intended for:
- Course Creators
- Managers

Neither Teachers nor Students generally interact with this block - unless they have been granted the necessary permissions.

# Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `block/dixeo_designer:addinstance` | Add the Course Designer block | Manager, Course Creator |
| `block/dixeo_designer:myaddinstance` | Add the block to My Moodle | Manager, Course Creator |
| `local/dixeo:create` | Create AI-generated courses | Manager, Course Creator |

# Course Design Workflow

The Course Designer guides users through a structured design process :
1. Enter course topic and/or learning objectives.
2. Optionally upload source documents
3. Choose a pedagogical template to apply during course design
4. Generate an initial course structure.
5. Review and edit sections, resources and activities.
6. Generate the course with sections, resources and activities
7. Review and publish the course.

# Editing Features

The designer includes rich editing capabilities including:
- drag-and-drop ordering of sections, resources and activities
- section collapsing/expanding
- inline editing
- text refinement
- undo support
- progress indicators
- generation status monitoring

# AI Capabilities

The Dixeo AI engine assists with generating:
- instructional sequencing
- learning objectives
- resources and activities
- module and course completion
- certificate issuing on course completion
All generated content remains editable before publication.

# Accessibility

The interface is designed to follow Moodle accessibility standards and includes:

- keyboard navigation
- accessible controls
- responsive layouts
- ARIA-compatible interactive elements

# Support

For support, documentation, or licensing information, contact the Dixeo Team: support@dixeo.com

## License

GNU GPL v3 or later
Copyright (c) 2026 Edunao

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License.
