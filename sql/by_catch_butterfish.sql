insert into by_catch_butterfish (
   batch_update_date
  ,val
  ,the_geom
)
select
   :d::timestamp
  ,replace(bcl_day1,'"','')
  ,the_geom
from
  scratch;
