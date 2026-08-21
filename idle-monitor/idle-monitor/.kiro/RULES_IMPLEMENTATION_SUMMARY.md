# 🛡️ SYSTEM RULES IMPLEMENTATION - SUMMARY

**Date**: 2026-06-03
**Status**: ✅ COMPLETE & ACTIVE

---

## What Was Created

### 1. Complete Rules Documentation
📄 **File**: `.kiro/SYSTEM_RULES.md`
- Complete detailed system protection rules
- 10 main protection areas
- Examples of what to do and NOT do
- Pre-implementation analysis template
- Checklist for every task

### 2. Quick Reference Guide
📄 **File**: `RULES_QUICK_REFERENCE.txt`
- Quick checklist for common tasks
- Protected areas list
- Database protection checklist
- Common mistakes to avoid
- Quick decision tree

### 3. Automated Reminder Hook
🔔 **Hook**: `system-rules-reminder`
- Triggers on EVERY task submission
- Reminds about rules automatically
- Asks for analysis before coding
- Confirms file protection
- Assesses risk level

---

## The 5 Golden Rules (NON-NEGOTIABLE)

```
1. JANGAN MERUSAK FITUR YANG SUDAH BERJALAN
2. JANGAN MENGHAPUS DATA YANG SUDAH ADA
3. JANGAN MENGUBAH FITUR YANG TIDAK DIMINTA
4. FOKUS HANYA PADA TASK YANG DIMINTA
5. SEMUA PERUBAHAN HARUS BACKWARD COMPATIBLE
```

---

## How It Works

### When you submit a task:

```
1. Hook triggers automatically
   ↓
2. System shows rules reminder
   ↓
3. You must do analysis:
   - Files to modify?
   - Files to NOT touch?
   - Database impact?
   - API impact?
   - Risk level?
   ↓
4. Based on risk:
   - GREEN: Proceed
   - YELLOW: Be careful
   - RED: Need approval
   ↓
5. Implement only planned changes
   ↓
6. Verify nothing broke
```

---

## Protected Areas (DO NOT TOUCH)

### 🔒 Authentication System
- AdminAuthController
- FrontendAuthController
- All middleware

### 🔒 Dashboard
- DashboardController (both admin & frontend)
- Dashboard views

### 🔒 Background Jobs
- ImportAlarmJob
- ProcessIdleAlarmJob
- SyncDeviceJob
- RefreshTokenJob

### 🔒 Scheduler
- app/Console/Kernel.php
- All cron jobs

### 🔒 API Endpoints
- All existing endpoints
- Response structure (backward compat only)

### 🔒 Database
- All existing tables
- All existing migrations
- User data

---

## Mandatory Before Coding

```markdown
EVERY TIME before writing code:

[ ] Read SYSTEM_RULES.md (5 min)
[ ] Identify files to modify
[ ] Identify files to NOT touch
[ ] Check database impact
[ ] Check API impact
[ ] Assess risk level
[ ] Show analysis to user (if needed)
[ ] Get approval (if HIGH risk)
[ ] Start coding only after approved
[ ] Test thoroughly
[ ] Verify nothing broke
[ ] Submit with confidence
```

---

## File Usage Guide

### Read First (Complete Details)
```
g:\project\vss\idle-monitor\.kiro\SYSTEM_RULES.md
- Full rules with examples
- 10 protection areas
- Templates and checklists
- When in doubt, read this!
```

### Quick Reference (During Task)
```
g:\project\vss\idle-monitor\RULES_QUICK_REFERENCE.txt
- Quick checklists
- Protected files list
- Common mistakes
- Decision tree
```

### Development Progress (What Exists)
```
g:\project\vss\idle-monitor\DEVELOPMENT_PROGRESS.md
- What's already built
- What's working
- What's protected
- What dependencies exist
```

---

## Examples of GOOD Changes

✅ **Adding new feature**:
- Create new controller
- Create new model
- Create new migration
- Create new views
- Create new routes
- Nothing existing changed

✅ **Bug fix**:
- Fix specific bug
- Don't refactor surrounding code
- Don't change behavior
- Backward compatible

✅ **Performance optimization**:
- Add database indexes
- Optimize queries
- Cache results
- No breaking changes

---

## Examples of BAD Changes

❌ **Breaking changes**:
- Removing API endpoint
- Changing response format
- Modifying database table structure
- Deleting columns
- Renaming existing fields

❌ **Scope creep**:
- Task: "Add Device Group"
- Also refactoring User model
- Also changing Dashboard
- Also modifying Auth

❌ **Data deletion**:
- Running migrate:fresh
- TRUNCATE table
- DROP table
- DELETE without WHERE

---

## Risk Assessment Guide

### GREEN Risk (Proceed)
- Only adding new features
- No changes to existing code
- No database modifications
- No API changes
- Fully backward compatible
- **Action**: Implement immediately

### YELLOW Risk (Be Careful)
- Some changes to existing code
- Database migration (add column)
- New API endpoint
- Minor breaking changes with migration plan
- **Action**: Test thoroughly, show analysis

### RED Risk (Need Approval)
- Major changes to core features
- Database structure changes
- API breaking changes
- Could affect multiple features
- **Action**: Stop, show analysis, wait for approval

---

## How Hook Reminder Works

### Triggered by
- Every new task submission (promptSubmit event)

### Shows
- System rules reminder
- 5 golden rules
- Analysis checklist
- Risk assessment questions

### Requires
- Understanding of rules
- Analysis before coding
- Confirmation ready to proceed

### Example Reminder:
```
🛡️ SYSTEM PROTECTION RULES REMINDER

⚠️ Baca: .kiro/SYSTEM_RULES.md

🔴 STRICT RULES (NON-NEGOTIABLE):
✅ JANGAN merusak fitur yang sudah berjalan
✅ JANGAN menghapus data
✅ JANGAN mengubah fitur yang tidak diminta
✅ FOKUS hanya pada task
✅ BACKWARD COMPATIBLE

📋 WAJIB ANALISIS SEBELUM KODE:
[ ] File to modify? (list)
[ ] Files to NOT touch? (confirm)
[ ] Database impact?
[ ] API impact?
[ ] Risk: GREEN/YELLOW/RED?
[ ] Need approval?

Lanjutkan dengan full analysis
```

---

## Documentation Map

```
Project Root: g:\project\vss\idle-monitor\
│
├─ .kiro/
│  └─ SYSTEM_RULES.md ..................... (Read first for complete rules)
│  └─ RULES_IMPLEMENTATION_SUMMARY.md ..... (This file - overview)
│
├─ RULES_QUICK_REFERENCE.txt ............. (Quick checklist during work)
├─ DEVELOPMENT_PROGRESS.md ............... (What's already built)
├─ SYSTEM_STATUS.txt ..................... (System status)
│
└─ Hook: system-rules-reminder
   (Automatically reminds on every task)
```

---

## Workflow Example

### Task: "Add Device Filter to Frontend Dashboard"

#### STEP 1: Receive Task
```
Task: Add Device Filter to Frontend Dashboard
Allow users to filter devices by group
```

#### STEP 2: Hook Triggers
```
🛡️ SYSTEM PROTECTION RULES REMINDER

Read SYSTEM_RULES.md before coding
Show analysis before implementing
```

#### STEP 3: Analyze
```
📋 ANALYSIS:

Files to modify:
- resources/views/frontend/dashboard.blade.php
- app/Http/Controllers/Frontend/DashboardController.php

Files to NOT touch:
- Authentication system ✅
- Other controllers ✅
- Database ✅
- Jobs ✅

Database impact: NONE
API impact: NONE
Risk level: GREEN (only UI changes + query modification)

Approval needed: NO
```

#### STEP 4: Implement
```
1. Add filter HTML to view
2. Add filter logic to controller
3. Test filtering works
4. Verify nothing broke
```

#### STEP 5: Verify
```
✅ Dashboard still works
✅ Charts still work
✅ Other filters work
✅ No data deleted
✅ Backward compatible
```

#### STEP 6: Submit
```
✅ Task complete
✅ No existing features broken
✅ No data lost
✅ Backward compatible
```

---

## Common Questions

### Q: Can I change this file?
**A**: Check `.kiro/SYSTEM_RULES.md` - Protected Areas section

### Q: Do I need permission?
**A**: 
- GREEN risk: NO
- YELLOW risk: Be careful
- RED risk: YES

### Q: What if I break something?
**A**: 
1. STOP immediately
2. Report the issue
3. ROLLBACK changes
4. Analyze what went wrong
5. Try different approach

### Q: Can I delete old migration?
**A**: NO - Never delete migrations. Create new one instead.

### Q: Can I change API response?
**A**: Only add new fields. Never remove/rename existing fields.

### Q: Can I use migrate:fresh?
**A**: NO - This deletes all data. Only use in testing environment.

### Q: What if scope changes?
**A**: Ask for clarification. Don't just expand scope.

---

## Quick Decision When In Doubt

```
1. Is it in the task description? YES → Continue
2. Will it break existing features? NO → Continue
3. Will it delete data? NO → Continue
4. Is it backward compatible? YES → Continue
5. All good? YES → Implement ✅

If ANY is NO, reconsider or ask for guidance.
```

---

## Key Takeaways

✅ **Always analyze before coding**
✅ **Show your plan first**
✅ **Only modify planned files**
✅ **Never delete data**
✅ **Keep backward compatibility**
✅ **Test thoroughly**
✅ **Verify nothing broke**
✅ **Stay in scope**

---

## Reference Checklist

Before every task:
```
[ ] Read SYSTEM_RULES.md section about task
[ ] Check RULES_QUICK_REFERENCE.txt
[ ] Identify all affected files
[ ] Check DEVELOPMENT_PROGRESS.md for context
[ ] Do analysis (file/db/api impact)
[ ] Assess risk level (GREEN/YELLOW/RED)
[ ] Show analysis if needed
[ ] Get approval if HIGH risk
[ ] Start coding only after ready
[ ] Test everything
[ ] Verify nothing broke
[ ] Submit
```

---

## Status

✅ **Rules System**: ACTIVE
✅ **Documentation**: COMPLETE
✅ **Hook Reminder**: ENABLED
✅ **Protection**: IN PLACE

**All systems ready to protect the project.**

---

**Remember**: 

> **Breaking existing features is WORSE than not adding new features.**
> 
> If in doubt, **ASK**. If not sure, **CHECK**. If worried, **DON'T DO IT**.
>
> **PROTECT what we have. IMPROVE carefully. BUILD safely.**

---

**Last Updated**: 2026-06-03
**Status**: 🔒 MANDATORY FOR ALL TASKS
**System**: ✅ ACTIVE & MONITORING
