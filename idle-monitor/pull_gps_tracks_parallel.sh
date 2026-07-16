#!/bin/bash
###############################################################################
# PARALLEL GPS TRACK DATA PULL
###############################################################################
# Description: Pull GPS tracks for multiple dates in parallel
# Usage: ./pull_gps_tracks_parallel.sh
# Author: Kiro AI Assistant
# Date: 2026-07-16
###############################################################################

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}     PARALLEL GPS TRACK DATA PULL${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Configuration
DATES=(
  "2026-07-01"
  "2026-07-02"
  "2026-07-03"
  "2026-07-04"
  "2026-07-05"
)

DELAY=60   # Delay between parallel starts (seconds) - PREVENT RATE LIMITING!

echo -e "${YELLOW}📋 Configuration:${NC}"
echo "   Total dates: ${#DATES[@]}"
echo "   Devices: All active devices"
echo "   Delay between starts: ${DELAY}s (prevent rate limiting)"
echo "   Estimated time: 10-15 minutes (parallel with delays)"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT:${NC}"
echo "   - API Howen has rate limiting"
echo "   - Each process starts with ${DELAY}s delay"
echo "   - Max 3-5 parallel processes recommended"
echo "   - If you see 'Login too frequently', increase DELAY"
echo ""

# Start pulling
echo -e "${GREEN}🚀 Starting parallel pull...${NC}"
echo ""

for date in "${DATES[@]}"; do
  echo -e "${BLUE}📥 Dispatching GPS pull: $date${NC}"
  
  nohup docker exec idle-monitor-app php artisan vss:pull-gps-tracks \
    --date=$date \
    --devices=all \
    --limit=0 \
    > "pull_gps_${date}.log" 2>&1 &
  
  echo "   Process started (PID: $!)"
  echo "   Waiting ${DELAY}s before next dispatch (prevent rate limiting)..."
  sleep $DELAY
done

echo ""
echo -e "${GREEN}✅ All processes started!${NC}"
echo ""
echo -e "${YELLOW}📋 Next steps:${NC}"
echo "   Monitor logs: tail -f pull_gps_*.log"
echo "   Check status: ps aux | grep vss:pull-gps"
echo ""
echo -e "${YELLOW}📊 Verify data after completion:${NC}"
echo "   docker exec idle-monitor-app php artisan tinker --execute=\"echo DB::table('gps_tracks_raw')->whereDate('gps_time', '2026-07-01')->count();\""
echo ""

