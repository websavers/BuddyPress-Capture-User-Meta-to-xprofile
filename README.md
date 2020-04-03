# BuddyPress Capture User Meta to xprofile
 BuddyPress user meta is stored in its own xprofile fields table. This captures standard update_user_meta() calls, detects when the key starts with bp_ and inserts the data to the xprofile field instead of the usermeta table

TIP: Meta field requests MUST use syntax bp_<xprofile_field_name>