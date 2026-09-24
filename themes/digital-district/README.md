# Digital District

An optional, standalone presentation theme for the Cianomalley portfolio. It is integrated with the canonical `cian-portfolio-core` plugin for content types, taxonomy, and shared project data.

## Integration contract

- Activate `cian-portfolio-core` before activating this theme. The plugin owns all portfolio content models and data.
- This theme owns templates, styling, interaction, and the contact form presentation. It does not register portfolio post types, fields, or GitHub synchronization.
- Oxygen remains the documented primary production presentation. Use this theme as an alternate frontend; do not combine both layout systems on the same site.
- Theme activation creates the Home, About, Contact, and Blog structural pages and  It does not import repositories, seed portfolio claims, publish example work, or delete existing WordPress content.
- Personal biography and location are editor-provided. Add only facts that have been verified by the site owner.

## Repository import

The plugin offers an explicit WP-CLI importer for public repositories. Configure a repository owner and explicit allowlist, review the mapping, and run it manually. Imports are drafts. The importer does not read private repositories or fetch README content.

## Local assets

Typography files are distributed under the SIL Open Font License (OFL). The SIL Open Font License attribution and full text are included as `assets/fonts/OFL.txt`.


