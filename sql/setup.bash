#!/bin/bash

psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_river_herring_bottom_trawl_rhode_island_grid.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_river_herring_bottom_trawl_rhode_island.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_river_herring_midwater_trawl_area2_grid.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_river_herring_midwater_trawl_area2.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_river_herring_midwater_trawl_capecod_grid.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_river_herring_midwater_trawl_capecod.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_scallop_yellowtail_area1_grid.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_scallop_yellowtail_area1.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_scallop_yellowtail_area2_grid.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_scallop_yellowtail_area2.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_scallop_yellowtail_nantucket_grid.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_scallop_yellowtail_nantucket.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_butterfish_grid.setup.sql
psql -U charlton -h 64.72.74.123 -d maracoos -f  by_catch_butterfish.setup.sql
