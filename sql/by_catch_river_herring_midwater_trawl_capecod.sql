insert into by_catch_river_herring_midwater_trawl_capecod (
   batch_update_date
  ,val
  ,the_geom
)
select
   :d::timestamp
  ,id
  ,the_geom
from
  scratch;
