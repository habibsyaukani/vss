# 📖 SYSTEM RULES DOCUMENTATION INDEX

**Welcome!** This folder contains all the system protection rules for the Idle Monitor project.

---

## 🚀 START HERE

### First Time? Read These Files In Order:

1. **This file** (you are here)
   - Overview and navigation guide

2. **`SYSTEM_RULES.md`** ← READ THIS FIRST
   - Complete detailed rules
   - Examples of good and bad changes
   - Pre-implementation checklist
   - Templates and guidelines

3. **`RULES_IMPLEMENTATION_SUMMARY.md`**
   - Quick overview of the system
   - How it works
   - Workflow examples
   - FAQ

---

## 📁 FILES IN THIS FOLDER

### Essential Rules Documents

#### 🟢 `SYSTEM_RULES.md` (PRIMARY)
**When**: Read at the start of project
**How long**: 15-20 minutes
**Contains**:
- 10 protection areas with detailed rules
- What you CAN do (✅)
- What you CANNOT do (❌)
- Examples of good and bad code
- Database protection rules
- API protection rules
- File structure protection
- Pre-implementation analysis template
- Mandatory checklist before coding

**Read if**: You want complete understanding of all rules

---

#### 🟡 `RULES_IMPLEMENTATION_SUMMARY.md` (OVERVIEW)
**When**: For quick overview and examples
**How long**: 5-10 minutes
**Contains**:
- Summary of what was created
- The 5 golden rules
- How the system works
- Workflow example (step by step)
- Common questions (FAQ)
- Key takeaways
- Risk assessment guide

**Read if**: You want high-level understanding

---

### Quick Reference

#### 🟠 `RULES_QUICK_REFERENCE.txt` (NOT IN THIS FOLDER)
**Location**: `g:\project\vss\idle-monitor\RULES_QUICK_REFERENCE.txt`
**When**: Use during task execution
**How long**: 2-3 minutes (quick checklist)
**Contains**:
- The 5 golden rules (quick format)
- Protected areas checklist
- Database do's and don'ts
- Mandatory checklist before coding
- Common mistakes
- Quick decision tree
- Quick reference template

**Use if**: You need quick reminder during work

---

## 🔄 THE WORKFLOW

### Every Time You Get a Task:

```
1. Hook reminds you
   ↓
2. Read relevant section of SYSTEM_RULES.md (5 min)
   ↓
3. Check RULES_QUICK_REFERENCE.txt (2 min)
   ↓
4. Do analysis:
   - Files to modify?
   - Files to NOT touch?
   - Database changes?
   - API changes?
   - Risk level?
   ↓
5. Show analysis (get approval if HIGH risk)
   ↓
6. Implement safely
   ↓
7. Test thoroughly
   ↓
8. Verify nothing broke
   ↓
9. Submit
```

---

## 📋 THE 5 GOLDEN RULES

These are non-negotiable. They MUST be followed every time:

```
1. JANGAN MERUSAK FITUR YANG SUDAH BERJALAN
2. JANGAN MENGHAPUS DATA YANG SUDAH ADA
3. JANGAN MENGUBAH FITUR YANG TIDAK DIMINTA
4. FOKUS HANYA PADA TASK YANG DIMINTA
5. SEMUA PERUBAHAN HARUS BACKWARD COMPATIBLE
```

---

## 🛡️ PROTECTED AREAS

These should NEVER be modified unless explicitly requested:

- ❌ Authentication System (AdminAuthController, FrontendAuthController, Middleware)
- ❌ Dashboard (DashboardController, views)
- ❌ Background Jobs (ImportAlarmJob, ProcessIdleAlarmJob, SyncDeviceJob, RefreshTokenJob)
- ❌ Scheduler (app/Console/Kernel.php)
- ❌ API Endpoints (existing endpoints and response structure)
- ❌ Database Tables (existing tables and migrations)
- ❌ User Data (never delete, truncate, or reset)

---

## 🚨 DATABASE PROTECTION

### ❌ NEVER DO THESE:

```bash
php artisan migrate:fresh      # Deletes ALL data
php artisan db:wipe            # Wipes entire database
php artisan migrate:reset      # Resets all migrations
DROP TABLE table_name;         # Deletes table
TRUNCATE TABLE table_name;     # Deletes all data
DELETE FROM table WHERE 1=1;   # Deletes all rows
```

### ✅ ALWAYS DO THIS INSTEAD:

```php
// Create NEW migration for changes:
php artisan make:migration add_column_to_table

// In migration file:
Schema::table('table_name', function (Blueprint $table) {
    $table->string('new_column')->nullable();  // Add column
    $table->dropColumn('old_column');          // Drop column (if safe)
});

// Run only new migrations:
php artisan migrate
```

---

## 🔍 BEFORE CODING - MANDATORY CHECKLIST

```markdown
[ ] Task is clear?
[ ] What exists already?
[ ] What will I change?
[ ] What will I NOT change?
[ ] Database impact?
[ ] API impact?
[ ] Risk level: GREEN / YELLOW / RED?
[ ] Need approval?
[ ] Show analysis first?

ONLY after all checked ✅, start coding
```

---

## 📚 HOW TO USE EACH FILE

### When you need...

**Complete understanding of all rules**
→ Read `SYSTEM_RULES.md`

**Quick overview and examples**
→ Read `RULES_IMPLEMENTATION_SUMMARY.md`

**Quick checklist during work**
→ Use `RULES_QUICK_REFERENCE.txt`

**Navigation guide**
→ You're reading it now!

**Development context (what exists)**
→ Read `../DEVELOPMENT_PROGRESS.md`

**System status**
→ Read `../SYSTEM_STATUS.txt`

---

## ❓ QUICK FAQ

### Q: Can I modify this file?
**A**: Check protected areas in `SYSTEM_RULES.md`

### Q: Do I need permission?
**A**: Only if HIGH risk. See risk assessment in `RULES_IMPLEMENTATION_SUMMARY.md`

### Q: What if I break something?
**A**: STOP immediately, report, rollback, analyze

### Q: Can I delete old migration?
**A**: NO. Always create new migration instead.

### Q: Can I use migrate:fresh?
**A**: NO. Only creates new migrations with `php artisan migrate`

### Q: What if scope changes mid-task?
**A**: Ask for clarification, don't just expand scope

### Q: Can I refactor unrelated code?
**A**: NO. Stay in scope. Only what's requested.

### Q: Can I optimize performance?
**A**: Only if it doesn't break existing functionality

---

## 🎯 QUICK START

1. **First Time**: Read `SYSTEM_RULES.md` completely (20 min)
2. **Each Task**: Check `RULES_QUICK_REFERENCE.txt` (2 min)
3. **Before Coding**: Show analysis (5 min)
4. **After Implementing**: Verify nothing broke (5 min)

---

## 📞 NEED HELP?

- **About specific rules**: Read `SYSTEM_RULES.md` section
- **Quick reference**: Use `RULES_QUICK_REFERENCE.txt`
- **Examples**: See `RULES_IMPLEMENTATION_SUMMARY.md`
- **Current status**: Read `../SYSTEM_STATUS.txt`
- **What's built**: Read `../DEVELOPMENT_PROGRESS.md`

---

## ✅ CONFIRMATION

You have successfully set up the system protection rules.

### What's Active:

✅ **Hook Reminder**: Triggers on every task
✅ **Documentation**: Complete and organized
✅ **Protection**: In place for all critical areas
✅ **Workflow**: Clear and mandatory

### Remember:

> **Breaking existing features is WORSE than not adding new features.**
> 
> If in doubt: **ASK**
> If not sure: **CHECK**
> If worried: **DON'T DO IT**

---

**Created**: 2026-06-03
**Status**: 🔒 ACTIVE & ENFORCED
**Version**: 1.0

*Read this whenever you need to understand the system protection rules.*

---

## 📖 DOCUMENTATION MAP

```
g:\project\vss\idle-monitor\
├─ .kiro/                                    (This folder)
│  ├─ README_RULES.md                       (You are here)
│  ├─ SYSTEM_RULES.md                       (Complete rules - read first!)
│  └─ RULES_IMPLEMENTATION_SUMMARY.md       (Overview & examples)
│
├─ RULES_QUICK_REFERENCE.txt                (Quick checklist - use during work)
├─ DEVELOPMENT_PROGRESS.md                  (What's built)
└─ SYSTEM_STATUS.txt                        (Current status)
```

---

Happy developing! Stay safe and follow the rules. 🛡️
