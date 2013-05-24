insert into by_catch_river_herring_bottom_trawl_rhode_island_grid (
   batch_update_date
  ,val
  ,the_geom
)
select
   :d::timestamp
  ,null
  ,the_geom
from
  scratch;
