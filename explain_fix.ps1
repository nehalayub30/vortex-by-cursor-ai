# Step 1: Abort existing rebase
git rebase --abort
# This cancels any ongoing rebase that's causing problems

# Step 2: Reset to origin/main
git fetch origin
git reset --hard origin/main
# This gets the latest version from GitHub and forces local files to match

# Step 3: Create new branch
git checkout -b clean-structure-20240318
# This creates a fresh branch to work with, avoiding main branch conflicts

# Step 4: Add and commit
git add -A
git commit -m "Clean structure update"
# This puts all your changes in a single, clean commit

# Step 5: Push new branch
git push -u origin clean-structure-20240318
# This pushes your changes to a new branch on GitHub 