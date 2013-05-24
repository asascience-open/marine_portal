function makeCharts() {
  var minDate;
  var maxDate;

  for (var i in chartData) {
    for (var j in chartData[i].v) {
      if (!minDate || new Date(j * 1000) < minDate) {
        minDate = new Date(j * 1000);
      }
      if (!maxDate || new Date(j * 1000) > maxDate) {
        maxDate = new Date(j * 1000);
      }
    }
  }

  var charts = {};
  for (var i in chartData) {
    if (new RegExp(/^Bycatch :/).test(i)) {
      // should be only 1 hit
      document.getElementById('title').innerHTML = i;
      // should be only 1 hit
      for (var j in chartData[i].v) {
        document.getElementById('subtitle').innerHTML = '<b>Catch amount in queried cell : ' + chartData[i].v[j] + '</b>';
      }
      continue;
    }
    var times  = [];
    var values = [];
    for (var j in chartData[i].v) {
      times.push(Number(j));
      values.push(Number(chartData[i].v[j]));
    }
    var d = new google.visualization.DataTable();
    d.addColumn('datetime','t');
    d.addColumn('number','val');
    d.addColumn({type : 'string',role : 'annotation'});
    d.addColumn('number','dummyFirst');
    d.addColumn('number','dummyLast');
    d.addColumn('number','Now');
    d.addColumn({type : 'string',role : 'annotation'});
    for (var j = 0; j < times.length; j++) {
      if (j == 0 && minDate < new Date(times[j] * 1000)) {
        d.addRow([minDate,null,null,values[j],null,null,null]);
      }
      else if (j == times.length - 1 && maxDate > new Date(times[j] * 1000)) {
        d.addRow([maxDate,null,null,null,values[j],null,null]);
      }
      if (j < times.length - 1 && new Date(times[j] * 1000) <= new Date() && new Date() <= new Date(times[j + 1] * 1000)) {
        var t  = new Date().getTime();
        var t0 = new Date(times[j] * 1000).getTime();
        var t1 = new Date(times[j + 1] * 1000).getTime();
        var v0 = values[j];
        var v1 = values[j + 1];
        if (t1 - t0 != 0) {
          var theta = Math.atan((v1 - v0) / (t1 - t0));
          d.addRow([new Date(),null,null,null,null,Math.sin(theta) * (t - t0) + v0,'Now']);
        }
      }

      d.addRow([
         new Date(times[j] * 1000)
        ,values[j]
        ,null
        ,null
        ,null
        ,null
        ,null
      ]);
    }
    charts[i] = d;
  }

  var c = 0;
  for (var i in charts) {
    var div = document.createElement('div');
    div.style.height = '150px';
    div.style.width  = chartWidth + 'px';
    div.id = 'charts' + c++;
    div.innerHTML = '&nbsp;'; // stupid IE!
    document.getElementById('charts').appendChild(div);
    var chart = new google.visualization.LineChart(div);
    var axis = {};
    if (new RegExp(/direction/).test(i)) {
      axis = {viewWindowMode : 'explicit',viewWindow : {min : 0,max : 360}};
    }
    else if (new RegExp(/speed|height/).test(i)) {
      axis = {viewWindowMode : 'explicit',viewWindow : {min : 0}};
    }
    chart.draw(
       charts[i]
      ,{
         title            : i
        ,legend           : {position : 'none'}
        ,hAxis            : {format : 'MMM d'}
        ,interpolateNulls : true
        ,annotation       : {5 : {style : 'line'}}
        ,curveType        : 'function'
        ,fontName         : '"Arial"' // stupid IE!
        ,vAxes            : [axis]
      } 
    );
  }
}
