#!/bin/bash

# actual data that changes somewhat frequently
./go.bash ~/Temp/fish/fish/bycatch/riverHerring/bottomTrawl/latest/RI2013_SMBT_Polygon.shp by_catch_river_herring_bottom_trawl_rhode_island '03/16/2013'
./go.bash ~/Temp/fish/fish/bycatch/riverHerring/midWaterTrawl/area2/latest/Area2_2013Polygon.shp by_catch_river_herring_midwater_trawl_area2 '03/19/2013'
./go.bash ~/Temp/fish/fish/bycatch/riverHerring/midWaterTrawl/cc/latest/CC2013Polygon.shp by_catch_river_herring_midwater_trawl_capecod '02/11/2013'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/closedArea1/2012/08-01_to_10-15/CA1_Data_from_10-15_catch.shp by_catch_scallop_yellowtail_area1 '10/15/2012'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/closedArea2/2012/08-01_to_10-15/CA2_Data_from_10-15_catch.shp by_catch_scallop_yellowtail_area2 '10/15/2012'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/nantucketLightship/2012/08-01_to_10-15/Nantucket_Data_catch.shp by_catch_scallop_yellowtail_nantucket '10/15/2012'
# needed to be reprojected
# ogr2ogr -s_srs '+proj=merc +lon_0=-76 +k=1 +x_0=0 +y_0=0 +ellps=WGS84 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs' -t_srs 'epsg:4326' MasterGrid31213_4326.shp MasterGrid31213.shp
./go.bash ~/Temp/fish/bycatch/MasterGrid31213_4326.shp by_catch_butterfish '03/12/2013'

# grids that you only do once in a while
./go.bash ~/Temp/fish/fish/bycatch/riverHerring/bottomTrawl/grid/RI2013_SMBT.shp by_catch_river_herring_bottom_trawl_rhode_island_grid '03/16/2013'
./go.bash ~/Temp/fish/fish/bycatch/riverHerring/midWaterTrawl/area2/grid/Area2_2013.shp by_catch_river_herring_midwater_trawl_area2_grid '03/19/2013'
./go.bash ~/Temp/fish/fish/bycatch/riverHerring/midWaterTrawl/cc/grid/CC2013.shp by_catch_river_herring_midwater_trawl_capecod_grid '02/11/2013'
# the complete scallop/yellowtail grid comes from 2 .shp's
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/closedArea1/2012/08-01_to_10-15/CA1_Grid.shp by_catch_scallop_yellowtail_area1_grid '10/15/2012'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/closedArea1/2012/08-01_to_10-15/CA1_Boundary.shp by_catch_scallop_yellowtail_area1_grid '10/15/2012'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/closedArea2/2012/08-01_to_10-15/CA2_Grid.shp by_catch_scallop_yellowtail_area2_grid '10/15/2012'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/closedArea2/2012/08-01_to_10-15/CA2_Boundary.shp by_catch_scallop_yellowtail_area2_grid  '10/15/2012'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/nantucketLightship/2012/08-01_to_10-15/Nantucket_Grid.shp by_catch_scallop_yellowtail_nantucket_grid '10/15/2012'
./go.bash ~/Temp/fish/fish/bycatch/scallopYellowtail/nantucketLightship/2012/08-01_to_10-15/Nantucket_Boundary.shp by_catch_scallop_yellowtail_nantucket_grid '10/15/2012'
# needed to be reprojected
# ogr2ogr -s_srs '+proj=merc +lon_0=-76 +k=1 +x_0=0 +y_0=0 +ellps=WGS84 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs' -t_srs 'epsg:4326' MasterGrid31213_4326.shp MasterGrid31213.shp
./go.bash ~/Temp/fish/bycatch/MasterGrid31213_4326.shp by_catch_butterfish_grid '03/12/2013'
