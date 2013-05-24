insert into by_catch_butterfish_grid (
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
