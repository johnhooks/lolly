# Commit

Create git commits for Lolly, the ECS-compatible HTTP logging plugin for WordPress.

## Commit Message Format

Use conventional commits without emoji:

```
type: subject line (max 50 chars)

Body paragraph explaining what changed functionally. Keep lines to 80
characters max. Focus on the "why" and "what" from a plugin user or
developer perspective.

Another paragraph if needed for context or related changes. Prefer prose
over lists unless listing truly makes more sense.
```

### Types
- `feat`: New feature or capability (redaction rules, whitelist, admin UI)
- `fix`: Bug fix (logging failures, redaction issues, REST API errors)
- `docs`: Documentation only
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `test`: Adding or updating tests
- `chore`: Maintenance tasks, dependencies, config

## Rules

1. **Title**: Max 50 characters, lowercase, no period
2. **Body**: Max 80 characters per line
3. **Length**: 2-3 paragraphs, 2-3 sentences each
4. **Style**: Concise prose, shorter is better
5. **No emoji**
6. **No co-author lines**
7. **No lists** unless they genuinely improve clarity
8. **Don't mention tests** unless the commit is specifically about testing

## Process

1. Run `git status` to see changes
2. Run `git diff --staged` or `git diff` to understand what changed
3. Run `git log --oneline -5` to see recent commit style
4. Stage the files with `git add`
5. Create the commit with Graphite using a heredoc:

```bash
git add <files>
gt create -m "$(cat <<'EOF'
type: subject line here

Body explaining the functional change from a user or developer perspective.
What does this enable? What problem does it solve?

Additional context if needed about approach or related changes.
EOF
)"
```

Note: Use `gt create` not `git commit`. This creates a new branch in the Graphite stack. The user will handle `gt submit` themselves.

## Examples

Good:
```
feat: add monaco json editor for redaction config

Users can now edit the full redaction and whitelist configuration as JSON in
the admin settings page. The editor validates against the existing schema and
shows inline errors for invalid configurations.

Monaco loads from CDN to avoid webpack bundling complexity with wp-scripts.
The existing save button handles persisting changes via the REST API.
```

Bad:
```
Update settings page 📝

- Added ConfigEditor component
- Updated settings-page.tsx
- Added editSettings action
- Installed @monaco-editor/react

Co-Authored-By: Claude <noreply@anthropic.com>
```
