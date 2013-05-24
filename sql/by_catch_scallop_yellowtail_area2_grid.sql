insert into by_catch_scallop_yellowtail_area2_grid (
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
