# ./go.bash path_to_shp.shp target_table_name mm/dd/yyyy

shp2pgsql -d $1 scratch | psql -U charlton -h 64.72.74.123 -d maracoos
psql -U charlton -h 64.72.74.123 -d maracoos -f $2.sql -v d="'$3'"
