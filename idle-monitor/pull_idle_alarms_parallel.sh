#!/bin/bash
###############################################################################
# PARALLEL IDLE ALARM DATA PULL
###############################################################################
# Description: Pull multiple dates in parallel (background jobs)
# Usage: ./pull_idle_alarms_parallel.sh
# Author: Kiro AI Assistant
# Date: 2026-07-16
###############################################################################

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}     PARALLEL IDLE ALARM DATA PULL${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Configuration
DATES=(
  "2026-07-01"
  "2026-07-02"
  "2026-07-03"
  "2026-07-04"
  "2026-07-05"
  "2026-07-06"
  "2026-07-07"
  "2026-07-08"
  "2026-07-09"
  "2026-07-10"
)

PAGES=100  # Pages per pull (100 = ~20k records max)

echo -e "${YELLOW}📋 Configuration:${NC}"
echo "   Total dates: ${#DATES[@]}"
echo "   Pages per date: $PAGES"
echo "   Estimated time: 5-10 minutes (parallel)"
echo ""

# Start pulling
echo -e "${GREEN}🚀 Starting parallel pull...${NC}"
echo ""

for date in "${DATES[@]}"; do
  echo -e "${BLUE}📥 Dispatching: $date${NC}"
  
  nohup docker exec idle-monitor-app php artisan howen:pull-alarms-date-range \
    --from=$date \
    --to=$date \
    --pages=$PAGES \
    > "pull_idle_${date}.log" 2>&1 &
  
  # Small delay to prevent overwhelming
  sleep 1
done

echo ""
echo -e "${GREEN}✅ All processes started!${NC}"
echo ""
echo -e "${YELLOW}📋 Next steps:${NC}"
echo "   Monitor logs: tail -f pull_idle_*.log"
echo "   Check status: ps aux | grep howen:pull"
echo "   Kill process: kill -9 <PID>"
echo ""
echo -e "${YELLOW}📊 Verify data after completion:${NC}"
echo "   docker exec idle-monitor-app php artisan tinker --execute=\"echo DB::table('idle_alarms')->whereDate('starting_time', '2026-07-01')->count();\""
echo ""

