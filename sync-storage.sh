#!/bin/bash
# Auto-sync storage files from local to hosting

LOCAL_DIR="/home/bangga/projects/aimms/storage/app/public"
REMOTE_USER="u264853682"
REMOTE_HOST="id-dci-web1762"
REMOTE_DIR="/home/u264853682/domains/aimms.banggagroup.com/public_html/storage/app/public"

echo "Starting sync..."
echo "Local: $LOCAL_DIR"
echo "Remote: $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR"

# Try SSH first with password prompt
ssh -v "$REMOTE_USER@$REMOTE_HOST" "mkdir -p $REMOTE_DIR" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "SSH connection OK. Syncing files..."
    find "$LOCAL_DIR" -type f \( -name "*.jpg" -o -name "*.png" -o -name "*.jpeg" \) | while read file; do
        rel_path="${file#$LOCAL_DIR/}"
        dir=$(dirname "$rel_path")
        ssh "$REMOTE_USER@$REMOTE_HOST" "mkdir -p $REMOTE_DIR/$dir"
        scp "$file" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/$rel_path"
        echo "✓ $rel_path"
    done
    echo "Sync complete!"
else
    echo "SSH failed. Pastikan Anda bisa SSH ke hosting:"
    echo "ssh $REMOTE_USER@$REMOTE_HOST"
    exit 1
fi
