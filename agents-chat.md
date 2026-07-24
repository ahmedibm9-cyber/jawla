# Agents Chat

Shared log for all agents working in this repo. Every agent reads this on
startup and appends what it's doing so others don't conflict or duplicate.

## Format

```
## [Agent Name] — [Timestamp]
- **Task:** what I'm working on
- **Files:** files I'm touching
- **Status:** in_progress | done | blocked
- **Notes:** anything other agents need to know
```

## Rules

1. **Read first.** Before starting any work, read this file to see who's
   doing what. Don't touch files another active agent is working on.
2. **Append only.** Never delete or edit another agent's entry — append yours
   at the bottom.
3. **Mark done.** When you finish, update your Status to `done` and add a
   one-line summary of what shipped.
4. **Check conflicts.** If two agents need the same file, the one who read it
   first wins. The other picks a different approach or waits.
5. **Clean up.** If you've been idle for >5 minutes with no progress, mark
   yourself `done` or `blocked` so others can proceed.

---

<!-- New entries go below this line -->
