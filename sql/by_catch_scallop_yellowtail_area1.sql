insert into by_catch_scallop_yellowtail_area1 (
   batch_update_date
  ,val
  ,the_geom
)
select
   :d::timestamp
  ,id::float
  ,the_geom
from
  scratch;
