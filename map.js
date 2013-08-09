var cp;
var map;
var previewMaps = {};
var proj3857   = new OpenLayers.Projection("EPSG:3857");
var proj900913 = new OpenLayers.Projection("EPSG:900913");
var proj4326   = new OpenLayers.Projection("EPSG:4326");

var maxFeatures   = 150;
var guaranteeFeatures = [];
var loadingLayers = {
   'forecasts'   : {}
  ,'weather'     : {}
  ,'wwa'         : {}
  ,'showByCatch' : {}
};

var searchLimitPerPage = 20;
var searchStart        = 1;
var activeSearches     = 0;

var chatLimitPerPage   = 100;

var session = {};

// allow contexts to return a real null
OpenLayers.Style.createLiteral = function(value, context, feature, property) {
  if (typeof value == "string" && value.indexOf("${") != -1) {
    value = OpenLayers.String.format(value, context, [feature, property]);
    value = (isNaN(value) || !value) ? value : parseFloat(value);
  }
  return value == 'null' ? null : value;
};

var dNow = new Date();
dNow.setUTCMinutes(0);
dNow.setUTCSeconds(0);
dNow.setUTCMilliseconds(0);
dNow.setUTCHours(dNow.getUTCHours() + (dNow.getUTCHours() % 2));

var weekday = [
   'Sunday'
  ,'Monday'
  ,'Tuesday'
  ,'Wednesday'
  ,'Thursday'
  ,'Friday'
  ,'Saturday'
];

var month = [
   'Jan'
  ,'Feb'
  ,'Mar'
  ,'Apr'
  ,'May'
  ,'Jun'
  ,'Jul' 
  ,'Aug'
  ,'Sep'
  ,'Oct'
  ,'Nov'
  ,'Dec'
];

var activeMode = 'observations';
var lastHighlight = {};
var highlightControl;
var selectControl;
var selectPopup;
var graphWin;
var zoomAlert = {
   fading  : true
  ,opacity : 0
  ,hits    : 0
};
var activeStationDetailsWindows = {};
var forecastUrls                = {};
var activeObs                   = {
   winds      : defaultObs == 'Winds'
  ,waves      : defaultObs == 'Waves'
  ,waterTemp  : defaultObs == 'WaterTemp'
  ,waterLevel : defaultObs == 'WaterLevel'
  ,all        : defaultObs == 'All'
  ,other      : false
};
var otherObs                    = {
   airTemperature : {
     topObsName : 'AirTemperature'
    ,niceName   : 'Air temperature'
    ,units      : 'F'
    ,niceValue  : function(value) {return Math.round(value * 1) / 1}
  }
  ,dissolvedOxygen : {
     topObsName : 'DissolvedOxygen'
    ,niceName   : 'Dissolved oxygen'
    ,units      : 'mg/l'
    ,niceValue  : function(value) {return Math.round(value * 10) / 10}
  }
  ,streamFlow : {
     topObsName : 'Streamflow'
    ,niceName   : 'Streamflow'
    ,units      : 'cfs'
    ,niceValue  : function(value) {return Math.round(value * 1) / 1}
 }
  ,turbidity : {
     topObsName : 'Turbidity'
    ,niceName   : 'Turbidity'
    ,units      : 'FNU'
    ,niceValue  : function(value) {return Math.round(value * 10) / 10}
 }
};

var observationsPanelHeights = {
   plain        : 129
  ,legendOffset : 56
}

var mapLayersStore = new Ext.data.ArrayStore({
   fields : ['panel','type','id','getMapUrl','getMapLayers','styles','format','timeParam','opacity','visibility','singleTile','moreInfo','bbox','legend']
  ,data   : mapLayersStoreData
});

var forecastMapsStore = new Ext.data.ArrayStore({
   fields : ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical','conditionsReport','liteLegendLabel','liteLegendImage']
  ,data   : forecastMapsStoreData
});
if (startupFCLayer) {
  forecastMapsStore.each(function(fcRec) {
    if (fcRec.get('id') == startupFCLayer) {
      fcRec.set('visibility',true);
      var layersOn = {};
      for (var i = 0; i < fcRec.get('wmsLayers').length; i++) {
        layersOn[fcRec.get('wmsLayers')[i]] = true;
      }
      mapLayersStore.each(function(mapRec) {
        if (mapRec.get('panel') == 'forecasts') {
          mapRec.set('visibility',layersOn[mapRec.get('id')] ? layersOn[mapRec.get('id')] : false); 
          if (startupFCContrast) {
            mapRec.set('opacity',startupFCContrast / 100);
          }
          mapRec.commit();
        }
      });
    }
    else {
      fcRec.set('visibility',false);
    }
    fcRec.commit();
  });
}

var weatherMapsStore = new Ext.data.ArrayStore({
   fields : ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical','conditionsReport','liteLegendLabel','liteLegendImage']
  ,data   : weatherMapsStoreData
});
if (startupWXLayer) {
  weatherMapsStore.each(function(fcRec) {
    if (fcRec.get('id') == startupWXLayer) {
      fcRec.set('visibility',true);
      var layersOn = {};
      for (var i = 0; i < fcRec.get('wmsLayers').length; i++) {
        layersOn[fcRec.get('wmsLayers')[i]] = true;
      }
      mapLayersStore.each(function(mapRec) {
        if (mapRec.get('panel') == 'weather') {
          mapRec.set('visibility',layersOn[mapRec.get('id')] ? layersOn[mapRec.get('id')] : false);
          if (startupWXContrast) {
            mapRec.set('opacity',startupWXContrast / 100);
          }
          mapRec.commit();
        }
      });
    }
    else {
      fcRec.set('visibility',false);
    }
    fcRec.commit();
  });
}

var byCatchMapsStore = new Ext.data.ArrayStore({
   fields : ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical']
  ,data   : byCatchMapsStoreData
});
if (startupbyCatchLayer) {
  byCatchMapsStore.each(function(fcRec) {
    if (fcRec.get('id') == startupbyCatchLayer) {
      fcRec.set('visibility',true);
      var layersOn = {};
      for (var i = 0; i < fcRec.get('wmsLayers').length; i++) {
        layersOn[fcRec.get('wmsLayers')[i]] = true;
      }
      mapLayersStore.each(function(mapRec) {
        if (mapRec.get('panel') == 'weather') {
          mapRec.set('visibility',layersOn[mapRec.get('id')] ? layersOn[mapRec.get('id')] : false);
          if (startupWXContrast) {
            mapRec.set('opacity',startupWXContrast / 100);
          }
          mapRec.commit();
        }
      });
    }
    else {
      fcRec.set('visibility',false);
    }
    fcRec.commit();
  });
}

var dataProvidersStore = new Ext.data.ArrayStore({
   fields : ['id']
  ,data   : [['ALL']]
});

function init() {
  var loadingMask = Ext.get('loading-mask');
  var loading = Ext.get('loading');

  //Hide loading message
  loading.fadeOut({duration : 0.2,remove : true});

  //Hide loading mask
  loadingMask.setOpacity(0.9);
  loadingMask.shift({
     xy       : loading.getXY()
    ,width    : loading.getWidth()
    ,height   : loading.getHeight()
    ,remove   : true
    ,duration : 1
    ,opacity  : 0.1
    ,easing   : 'bounceOut'
  });

  Ext.QuickTips.init();

  // don't remember window settings
  Ext.override(Ext.Component,{
    stateful : false
  });

  cp = new Ext.state.CookieProvider({
    expires : new Date(new Date().getTime()+(1000*60*60*24*30)) //30 days
  });
  Ext.state.Manager.setProvider(cp);


  // stoping a cascade doesn't work out of the box
  Ext.override(Ext.tree.TreeNode,{
    cascade : function(fn, scope, args){
      if (fn.call(scope || this, args || this) !== false){
        var cs = this.childNodes;
        for(var i = 0, len = cs.length; i < len; i++) {
          if (cs[i].cascade(fn, scope, args)==false) return false;
        }
      }
      else return false
    }
  });

  var tbarItems = [];
  if (search) {
    tbarItems = [
      new Ext.ux.form.SearchField({
         emptyText       : 'Enter keywords to find data.'
        ,cls             : (Ext.isChrome || Ext.isSafari) ? 'chromeInput' : ''
        ,width           : 220
        ,border          : false
        ,id              : 'anyTextSearchField'
        ,paramName       : 'anyText'
        ,wrapFocusClass  : ''
        ,onTrigger1Click : function() {
          if(this.hasSearch){
              this.reset();
              // having a tough time w/ the focus, so force a reset for emptyText
              this.setRawValue(this.emptyText);
              this.el.addClass(this.emptyClass);
              var o = {start: 0};
              if (this.store) {
                this.store.baseParams = this.store.baseParams || {};
                this.store.baseParams[this.paramName] = '';
                this.store.reload({params:o});
              }
              this.triggers[0].hide();
              this.hasSearch = false;
          }
        }
        ,onTrigger2Click : function() {
          var v = this.getRawValue();
          if(v.length < 1){
              this.onTrigger1Click();
              return;
          }
          var o = {start: 0};
          if (this.store) {
            this.store.baseParams = this.store.baseParams || {};
            this.store.baseParams[this.paramName] = v;
            this.store.reload({params:o});
          }
          runQuery();
          this.hasSearch = true;
          this.triggers[0].show();
        }
      })
      ,'->'
      ,{
         icon  : 'img/printer16.png'
        ,tooltip : 'Print the active map'
        ,handler : function() {printMap()}
      }
      ,' '
      ,' '
      ,' '
      ,{
         icon  : 'img/link16.png'
        ,tooltip : 'Link to this map session'
        ,handler : function() {linkMap()}
      }
      ,' '
      ,' '
      ,' '
      ,{
         icon  : 'img/help16.png'
        ,tooltip : 'Learn how to use this site'
        ,handler : function() {helpPage ? window.open(helpPage) : showSplash(true)}
      }
    ];
  }
  else {
    tbarItems = [
      {
         icon    : 'img/printer16.png'
        ,tooltip : 'Print the active map'
        ,handler : function() {printMap()}
        ,text    : 'Print'
      }
      ,' '
      ,' '
      ,' '
      ,{
         icon    : 'img/link16.png'
        ,tooltip : 'Link to this map session'
        ,handler : function() {linkMap()}
        ,text    : 'Link'
      }
      ,'->'
    ];
    if (chat) {
      tbarItems.push([
        {
           icon    : 'img/comments16.png'
          ,tooltip : 'Provide feedback on this website'
          ,handler : function() {startChat()}
          ,text    : 'Comments'
        }
        ,' '
        ,' '
        ,' '
      ]);
    }
    tbarItems.push([
      {
         icon  : 'img/help16.png'
        ,tooltip : 'Learn how to use this site'
        ,handler : function() {helpPage ? window.open(helpPage) : showSplash(true)}
        ,text    : 'Help'
      }
    ]);
  }

  var weatherStationsTreePanel = new Ext.tree.TreePanel({
     id          : 'weatherStationsTreePanel'
    ,anchor      : '100% -' + (observationsPanelHeights.plain + (new RegExp(/^(Winds|Waves|WaterTemp|WaterLevel)$/).test(defaultObs) ? observationsPanelHeights.legendOffset : 0))
    ,border      : true
    ,loader      : new Ext.tree.TreeLoader({preloadChildren : true})
    ,autoScroll  : true
    ,rootVisible : false
    ,root        : new Ext.tree.AsyncTreeNode()
    ,tbar        : {items : new Ext.form.ComboBox({
       id             : 'weatherStationsQuickFindComboBox'
      ,width          : 285
      ,store          : new Ext.data.ArrayStore({
         fields : ['lbl','provider','descr']
        ,filter : function(property,value) {
          if (value == '') {
            return true;
          }
          this.filterBy(function(record,id) {
            return record.get('lbl').toLowerCase().indexOf(value.toLowerCase()) >= 0
          });
        }
      })
      ,forceSelection : true
      ,triggerAction  : 'all'
      ,emptyText      : 'Enter part of a station name to find it on the map.'
      ,cls            : Ext.isChrome ? 'chromeInput' : ''
      ,selectOnFocus  : true
      ,mode           : 'local'
      ,displayField   : 'lbl'
      ,listeners      : {select : function(cb,rec,i) {
        var tree = Ext.getCmp('weatherStationsTreePanel');
        tree.getRootNode().cascade(function(n) {
          if (n.attributes.provider == rec.get('provider') && n.attributes.text == rec.get('descr')) {
            tree.selectPath(n.getPath());
            n.ui.focus();
            Ext.getCmp('weatherStationsOptionsAllButton').toggle(true);
            selectWeatherStationType('all',{provider : n.attributes.provider,descr : n.attributes.text});
            cb.clearValue();
            return false;
          }
        });
      }}
      ,doQuery : function(q, forceAll){
        q = Ext.isEmpty(q) ? '' : q;
        var qe = {
          query: q,
          forceAll: forceAll,
          combo: this,
          cancel:false
        };
        if(this.fireEvent('beforequery', qe)===false || qe.cancel){
          return false;
        }
        q = qe.query;
        forceAll = qe.forceAll;
        if(forceAll === true || (q.length >= this.minChars)){
          if(this.lastQuery !== q){
            this.lastQuery = q;
            if(this.mode == 'local'){
              this.selectedIndex = -1;
              if(forceAll){
                this.store.clearFilter();
              }else{
                this.store.filter(this.displayField, q, true); // supply the anyMatch option
              }
              this.onLoad();
            }else{
              this.store.baseParams[this.queryParam] = q;
              this.store.load({
                params: this.getParams(q)
              });
              this.expand();
            }
          }else{
            this.selectedIndex = -1;
            this.onLoad();
          }
        }
      }
    })}
    ,listeners   : {click : function(node,e) {
      Ext.getCmp('weatherStationsOptionsAllButton').toggle(true);
      selectWeatherStationType('all',{provider : node.attributes.provider,descr : node.attributes.text});
    }}
  });

  var sPanel = {
     region    : 'south'
    ,html      : southPanel.html
    ,height    : Number(southPanel.height) - 1
    ,border    : false
    ,bodyStyle : 'padding-left:7px'
  };
  if (byCatch) {
    var sm = new Ext.grid.CheckboxSelectionModel();
    sPanel =  {height : 300,border : false,region : 'south',id : 'byCatchPanel',bodyStyle : 'padding:5px 5px;overflow-x:hidden',layout : 'border',items : [
      new Ext.TabPanel({
         activeTab      : 'showByCatchPanel'
        ,id             : 'byCatchTabPanel'
        ,plain          : true
        ,region         : 'center'
        ,width          : 1
        ,border         : false
        ,bodyStyle      : 'padding:3px'
        ,resizeTabs     : true
        ,deferredRender : false
        ,items          : [
          new Ext.FormPanel({
             border  : false
            ,title   : 'Show<br>bycatch data'
            ,iconCls : 'showByCatchTab'
            ,id      : 'showByCatchPanel'
            ,cls     : 'formPanel'
            ,layout  : 'anchor'
            ,items   : [
              {border : false,height : 90,layout : 'form',labelSeparator : '',labelWidth : 70,items : [
                 {cls : 'directionsText',html : 'Select a bycatch type to view.  When viewing model forecasts, click inside a bycatch grid to initiate an environmental report.',border : false}
                ,{html : '<img height=7 src="img/blank.png">',border : false}
                ,new Ext.form.ComboBox({
                   store          : byCatchMapsStore
                  ,id             : 'byCatchMapsTypeComboBox'
                  ,displayField   : 'id'
                  ,valueField     : 'id'
                  ,mode           : 'local'
                  ,forceSelection : true
                  ,triggerAction  : 'all'
                  ,editable       : false
                  ,value          : byCatchMapsStore.getAt(byCatchMapsStore.findExact('visibility',true)).get('id')
                  ,fieldLabel     : 'Type'
                  ,width          : 218
                  ,tpl            : new Ext.XTemplate(
                    '<tpl for=".">'
                      ,'<div class="x-combo-list-item">'
                      ,'{[!values.wmsLayers ? "<font color=gray>" : "&nbsp;&nbsp;&nbsp;"]}'
                      ,'{id}'
                      ,'{[!values.wmsLayers ? "</font>" : ""]}'
                      ,'</div>'
                    ,'</tpl>'
                  )
                  ,listeners      : {
                    focus  : function() {
                      map.zoomToByCatch = true;
                    }
                    ,blur  : function() {
                      map.zoomToByCatch = false;
                    }
                    ,select : function(combo,rec) {
                      hideAllByCatchLayers();
                      if (!rec) {
                        return;
                      }
                      var layers = rec.get('wmsLayers');
                      for (var i = 0; i < layers.length; i++) {
                        map.getLayersByName(layers[i])[0].setVisibility(true);
                        if (map.getLayersByName(layers[i])[0].bbox && map.zoomToByCatch) {
                          var bbox = map.getLayersByName(layers[i])[0].bbox.split(',');
                          map.zoomToExtent(new OpenLayers.Bounds(
                             Number(bbox[0]) - 0.25
                            ,Number(bbox[1]) - 0.25
                            ,Number(bbox[2]) + 0.25
                            ,Number(bbox[3]) + 0.25
                          ).transform(proj4326,proj900913));
                        }
                      }
                      syncMapLegends('byCatchMapsTypeComboBox','showByCatchLegendPanel');
                    }
                  }
                })
              ]}
              ,{
                 id         : 'showByCatchLegendPanel'
                ,xtype      : 'box'
                ,anchor     : '100% -90'
                ,autoScroll : true
                ,autoEl     : {html : '&nbsp;'}
                ,style     : {
                   border     : '1px solid #99BBE8'
                  ,background : '#FFFFFF'
                }
                ,listeners  : {render : function(panel) {
                  if (panel.contentToLoad) {
                    panel.update(panel.contentToLoad);
                  }
                }}
              }
            ]
          })
          ,new Ext.FormPanel({
             title   : 'Hide<br>bycatch data'
            ,iconCls : 'hideByCatchTab'
            ,border  : false
            ,cls     : 'formPanel'
            ,layout  : 'fit'
            ,items   : {cls : 'directionsText',html : 'Click the Show bycatch data tab to explore catch data and create reports.',border : false}
          })
        ]
        ,listeners : {afterrender : function(p) {
          p.addListener('tabchange',function(p,tab) {
            Ext.getCmp('byCatchPanel').setHeight(tab.id == 'showByCatchPanel' ? 300 : 95);
            Ext.getCmp('mainControlPanel').doLayout();
            if (tab.id == 'showByCatchPanel') {
              map.zoomToByCatch = true;
              var combo = Ext.getCmp('byCatchMapsTypeComboBox');
              combo.fireEvent('select',combo,combo.getStore().getAt(combo.getStore().findExact('id',combo.getValue())));
            }
            else {
              hideAllByCatchLayers();
            }
            var l = map.getLayersByName('queryPt')[0];
            l.removeFeatures(l.features);
          });
        }}
      })
    ]}
  }

  new Ext.Viewport({
     layout : 'border'
    ,items  : [
      {
         region    : 'north'
        ,html      : banner.html
        ,height    : Number(banner.height)
        ,border    : false
      }
      ,{
         region      : 'east'
        ,hidden      : viewer == 'lite'
        ,width       : 315
        ,layout      : 'border'
        ,id          : 'mainControlPanel'
        ,tbar        : tbar ? tbarItems : false
        ,items       : [
          {border : false,region : 'center',id : 'queryFiltersPanel',bodyStyle : 'padding:5px 5px;overflow-x:hidden',layout : 'border',items : [
            new Ext.TabPanel({
               activeTab      : 'observationsPanel'
              ,id             : 'mapTabPanel'
              ,region         : 'center'
              ,width          : 1
              ,border         : false
              ,resizeTabs     : Ext.isIE
              ,bodyStyle      : 'padding:3px'
              ,deferredRender : false
              ,items          : [
                 new Ext.FormPanel({
                   border  : false
                  ,title   : 'Point<br>observations'
                  ,iconCls : 'observationsTab'
                  ,id      : 'observationsPanel'
                  ,cls     : 'formPanel'
                  ,layout  : 'anchor'
                  ,items   : [
                    {border : false,height : observationsPanelHeights.plain + (new RegExp(/^(Winds|Waves|WaterTemp|WaterLevel)$/).test(defaultObs) ? observationsPanelHeights.legendOffset : 0),items : [
                       {cls : 'directionsText',html : 'Select an icon below or from the drop down menu to view near real-time observations.',border : false}
                      ,{html : '<img height=7 src="img/blank.png">',border : false}
                      ,{
                         border      : false
                        ,layout      : 'column'
                        ,items       : [
                          {
                             columnWidth  : 0.25 * (4 - availableObs.hits) / 2
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : availableObs.hits == 4
                          }
                          , new Ext.Button({
                             columnWidth  : 0.23
                            ,icon         : 'img/Wind-Flag-Storm-icon.png'
                            ,tooltip      : 'View wind observations'
                            ,scale        : 'large'
                            ,iconAlign    : 'top'
                            ,toggleGroup  : 'weatherStationsOptionsDefaultObsGroup'
                            ,id           : 'weatherStationsOptionsWindsButton'
                            ,enableToggle : true
                            ,allowDepress : false
                            ,pressed      : defaultObs == 'Winds'
                            ,handler      : function() {
                              selectWeatherStationType('winds');
                            }
                            ,hidden       : !availableObs['Winds']
                          })
                          ,{
                             columnWidth  : 0.02
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : !availableObs['Winds']
                          }
                          ,new Ext.Button({
                             columnWidth  : 0.24
                            ,icon         : 'img/wave-icon.png'
                            ,tooltip      : 'View wave observations'
                            ,scale        : 'large'
                            ,iconAlign    : 'top'
                            ,toggleGroup  : 'weatherStationsOptionsDefaultObsGroup'
                            ,id           : 'weatherStationsOptionsWavesButton'
                            ,enableToggle : true
                            ,allowDepress : false
                            ,pressed      : defaultObs == 'Waves'
                            ,handler      : function() {
                              selectWeatherStationType('waves');
                            }
                            ,hidden       : !availableObs['Waves']
                          })
                          ,{
                             columnWidth  : 0.02
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : !availableObs['Waves']
                          }
                          ,new Ext.Button({
                             columnWidth  : 0.24
                            ,icon         : 'img/thermometer.png'
                            ,tooltip      : 'View water temperature observations'
                            ,scale        : 'large'
                            ,iconAlign    : 'top'
                            ,toggleGroup  : 'weatherStationsOptionsDefaultObsGroup'
                            ,id           : 'weatherStationsOptionsWaterTempButton'
                            ,enableToggle : true
                            ,allowDepress : false
                            ,pressed      : defaultObs == 'WaterTemp'
                            ,handler      : function() {
                              selectWeatherStationType('waterTemp');
                            }
                            ,hidden       : !availableObs['WaterTemp']
                          })
                          ,{
                             columnWidth  : 0.02
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : !availableObs['WaterTemp']
                          } 
                          ,new Ext.Button({
                             columnWidth  : 0.23
                            ,icon         : 'img/draw_wave.png'
                            ,tooltip      : 'View water level observations'
                            ,scale        : 'large'
                            ,iconAlign    : 'top'
                            ,toggleGroup  : 'weatherStationsOptionsDefaultObsGroup'
                            ,id           : 'weatherStationsOptionsWaterLevelButton'
                            ,enableToggle : true
                            ,allowDepress : false
                            ,pressed      : defaultObs == 'WaterLevel'
                            ,handler      : function() {
                              selectWeatherStationType('waterLevel');
                            }
                            ,hidden       : !availableObs['WaterLevel']
                          })
                        ]
                      }
                      ,{
                         border      : false
                        ,layout      : 'column'
                        ,items       : [
                          {
                             columnWidth  : 0.25 * (4 - availableObs.hits) / 2
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : availableObs.hits == 4
                          }
                          ,{
                             columnWidth  : 0.23
                            ,border       : false
                            ,html         : '<table id="weatherStationsOptionsWindsLabel" class="belowButtonsText' + (defaultObs == 'Winds' ? 'Black' : 'Gray') + '" width="100%"><tr><td>Winds</td></tr></table>'
                            ,hidden       : !availableObs['Winds']
                          }
                          ,{
                             columnWidth  : 0.02
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : !availableObs['Winds']
                          }
                          ,{
                             columnWidth  : 0.23
                            ,border       : false
                            ,html         : '<table id="weatherStationsOptionsWavesLabel" class="belowButtonsText' + (defaultObs == 'Waves' ? 'Black' : 'Gray') + '" width="100%"><tr><td>Waves</td></tr></table>'
                            ,hidden       : !availableObs['Waves']
                          }
                          ,{
                             columnWidth  : 0.02
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : !availableObs['Waves']
                          }
                          ,{
                             columnWidth  : 0.24
                            ,border       : false
                            ,html         : '<table id="weatherStationsOptionsWaterTempLabel" class="belowButtonsText' + (defaultObs == 'WaterTemp' ? 'Black' : 'Gray') + '" width="100%"><tr><td>Water&nbsp;temp</td></tr></table>'
                            ,hidden       : !availableObs['WaterTemp']
                          }
                          ,{
                             columnWidth  : 0.02
                            ,border       : false
                            ,html         : '&nbsp;'
                            ,hidden       : !availableObs['WaterTemp']
                          }
                          ,{
                             columnWidth  : 0.23
                            ,border       : false
                            ,html         : '<table id="weatherStationsOptionsWaterLevelLabel" class="belowButtonsText' + (defaultObs == 'WaterLevel' ? 'Black' : 'Gray') + '" width="100%"><tr><td>Water&nbsp;level</td></tr></table>'
                            ,hidden       : !availableObs['WaterLevel']
                          }
                        ]
                      }
                      ,{
                         border      : false
                        ,layout      : 'column'
                        ,items       : [
                          new Ext.Button({
                             columnWidth  : 0.50
                            ,toggleGroup  : 'weatherStationsOptionsDefaultObsGroup'
                            ,id           : 'weatherStationsOptionsOtherButton'
                            ,tooltip      : 'View other observations by selecting from this list'
                            ,enableToggle : true
                            ,allowDepress : false
                            ,cls          : 'belowButtonsTextBlack'
                            ,width        : 150
                            ,menu         : {id : 'otherObsMenu',items : []}
                            ,handler      : function() {
                              for (var o in otherObs) {
                                if (Ext.getCmp('otherObs' + otherObs[o].topObsName).checked) {
                                  selectWeatherStationType(o);
                                }
                              }
                            }
                            ,pressed      : !new RegExp(/^(Winds|Waves|WaterTemp|WaterLevel|All)$/).test(defaultObs)
                            ,listeners    : {afterrender : function(b) {
                              var firstOption = true;
                              for (var o in otherObs) {
                                activeObs[o] = false;
                                if (b.pressed) {
                                  Ext.getCmp('weatherStationsOptionsOtherButton').setText(makeNiceTopObs(defaultObs).name);
                                } 
                                else if (firstOption) {
                                  Ext.getCmp('weatherStationsOptionsOtherButton').setText('Other observations');
                                }
                                Ext.getCmp('otherObsMenu').add(
                                  {
                                     text         : makeNiceTopObs(otherObs[o].topObsName).name
                                    ,group        : 'otherObs'
                                    ,id           : 'otherObs' + otherObs[o].topObsName
                                    ,checked      : b.pressed && otherObs[o].topObsName == defaultObs
                                    ,handler      : function(el) {
                                      b.toggle(true);
                                      for (var o in otherObs) {
                                        if (makeNiceTopObs(otherObs[o].topObsName).name == el.text) {
                                          Ext.getCmp('weatherStationsOptionsOtherButton').setText(makeNiceTopObs(otherObs[o].topObsName).name);
                                          selectWeatherStationType(o);
                                        }
                                      }
                                    }
                                  }
                                );
                                firstOption = false;
                              }
                            }}
                          })
                          ,{
                             columnWidth  : 0.26
                            ,border       : false
                            ,html         : '&nbsp;'
                          }
                          ,new Ext.Button({
                             columnWidth  : 0.24
                            ,toggleGroup  : 'weatherStationsOptionsDefaultObsGroup'
                            ,id           : 'weatherStationsOptionsAllButton'
                            ,tooltip      : 'View all observation stations'
                            ,enableToggle : true
                            ,allowDepress : false
                            ,handler      : function() {
                              selectWeatherStationType('all');
                            }
                            ,cls          : 'belowButtonsTextBlack'
                            ,text         : 'Stations'
                            ,pressed      : defaultObs == 'All'
                          })
                        ]
                      }
                      ,{id : 'obsLegendDivider',html : '<img height=7 src="img/blank.png">',border : false}
                      ,{
                         border      : true
                        ,layout      : 'column'
                        ,bodyStyle   : 'padding : 6px'
                        ,hidden      : !new RegExp(/^(Winds|Waves|WaterTemp|WaterLevel)$/).test(defaultObs)
                        ,id          : 'obsLegend'
                        ,items       : {
                           columnWidth : 1
                          ,height      : 35
                          ,border      : false
                          ,html        : makeObsLegend(defaultObs).html
                        }
                      }
                    ]}
                    ,weatherStationsTreePanel
                  ]
                  ,listeners : {afterrender : function(el) {
                    el.doLayout();
                  }}
                })
                ,new Ext.FormPanel({
                   id      : 'weatherPanel'
                  ,title   : weatherTab + '<br>observations'
                  ,iconCls : weatherTab.toLowerCase() + 'Tab'
                  ,border  : false
                  ,cls     : 'formPanel'
                  ,layout  : 'anchor'
                  ,items   : [
                    {border : false,height : 108,layout : 'form',labelSeparator : '',labelWidth : 70,items : [
                       {cls : 'directionsText',html : 'These overlays are in near-real time.  The weather hazard layers are controlled separately below.',border : false}
                      ,{html : '<img height=7 src="img/blank.png">',border : false}
                      ,new Ext.form.ComboBox({
                         store          : weatherMapsStore
                        ,id             : 'weatherMapsTypeComboBox'
                        ,displayField   : 'id'
                        ,valueField     : 'id'
                        ,mode           : 'local'
                        ,forceSelection : true
                        ,triggerAction  : 'all'
                        ,editable       : false
                        ,value          : (weatherMapsStore.findExact('visibility',true) >= 0 ? weatherMapsStore.getAt(weatherMapsStore.findExact('visibility',true)).get('id') : weatherMapsStore.getAt((weatherMapsStore.getCount() > 1 ? 1 : 0)).get('id'))
                        ,fieldLabel     : 'Type'
                        ,width          : 218
                        ,tpl            : new Ext.XTemplate(
                          '<tpl for=".">'
                            ,'<div class="x-combo-list-item">'
                            ,'{[!values.wmsLayers ? "<font color=gray>" : "&nbsp;&nbsp;&nbsp;"]}'
                            ,'{id}'
                            ,'{[!values.wmsLayers ? "</font>" : ""]}'
                            ,'</div>'
                          ,'</tpl>'
                        )
                        ,listeners      : {select : function(combo,rec) {
                          hideAllLayers(); 
                          if (rec) {
                            var layers = rec.get('wmsLayers');
                            for (var i = 0; i < layers.length; i++) {
                              map.getLayersByName(layers[i])[0].setVisibility(true);
                            }
                          }
                          syncMapLegends('weatherMapsTypeComboBox','weatherLegendPanel');
                        }}
                      })
                      ,{html : '<img height=12 src="img/blank.png">',border : false}
                      ,new Ext.Slider({
                         fieldLabel     : 'Contrast'
                        ,id             : 'weatherVisibilitySlider'
                        ,width          : 215
                        ,minValue       : 0
                        ,maxValue       : 100
                        ,plugins        : new Ext.slider.Tip({
                          getText : function(thumb) {
                            return String.format('{0}%',thumb.value);
                          }
                        })
                        ,listeners      : {'change' : function(slider,val) {
                          var sto    = Ext.getCmp('weatherMapsTypeComboBox').getStore();
                          var layers = sto.getAt(sto.findExact('id',Ext.getCmp('weatherMapsTypeComboBox').getValue())).get('wmsLayers');
                          for (var i = 0; i < layers.length; i++) {
                            map.getLayersByName(layers[i])[0].setOpacity(val / 100);
                          }
                        }}
                      })
                    ]}
                    ,{
                       id         : 'weatherLegendPanel'
                      ,xtype      : 'box'
                      ,anchor     : '100% -287'
                      ,autoScroll : true
                      ,autoEl     : {html : '&nbsp;'}
                      ,style     : {
                         border     : '1px solid #99BBE8'
                        ,background : '#FFFFFF'
                      }
                      ,listeners  : {render : function(panel) {
                        if (panel.contentToLoad) {
                          panel.update(panel.contentToLoad);
                        }
                      }}
                    }
                    ,{border : false,html : '<img src="img/blank.png" height=3>',height : 5,anchor : '-155'}
                    ,{border : false,height : 29,layout : 'form',labelSeparator : '',labelWidth : 190,items : new Ext.form.RadioGroup({
                       fieldLabel : 'Show weather hazards?'
                      ,id         : 'wwaRadioGroup'
                      ,items      : [
                         {boxLabel : 'Yes',name : 'wwaRadioGroup',id : 'wwaRadioGroupYes',checked : startupWWA}
                        ,{boxLabel : 'No' ,name : 'wwaRadioGroup',id : 'wwaRadioGroupNo' ,checked : !startupWWA}
                      ]
                      ,listeners  : {change : function(rg,radio) {
                        map.getLayersByName('Watches, warnings, and advisories')[0].setVisibility(radio.id == 'wwaRadioGroupYes');
                        map.getLayersByName('NHC storm tracks')[0].setVisibility(radio.id == 'wwaRadioGroupYes');
                        map.getLayersByName('Marine zones')[0].setVisibility(radio.id == 'wwaRadioGroupYes');
                        if (radio.id == 'wwaRadioGroupYes') {
                          Ext.getCmp('wwaLegendPanel').enable();
                        }
                        else {
                          Ext.getCmp('wwaLegendPanel').disable();
                          var l = map.getLayersByName('queryPt')[0];
                          l.removeFeatures(l.features);
                        }
                      }} 
                    })}
                    ,{id : 'wwaLegendPanel',height : 145,layout : 'anchor',disabled : !startupWWA,items : [
                       {height : 55,cls : 'directionsText',html : 'Access hazard text by clicking in a yellow, orange, or red shaded area on the map.<br><table width="100%"><tr><td align=center><img style="padding-top:2px" src="img/wwa.png"></td></tr></table>',border : false}
                      ,{anchor : '100% -55',border : false,id : 'wwaActivityPanel',cls : 'directionsTextNoPadding',autoScroll : true}
                    ]}
                  ]
                })
                ,new Ext.FormPanel({
                   id      : 'forecastsPanel'
                  ,title   : 'Model<br>forecasts'
                  ,iconCls : 'forecastsTab'
                  ,border  : false
                  ,cls     : 'formPanel'
                  ,layout  : 'anchor'
                  ,items   : [
                    {border : false,height : 150,layout : 'form',labelSeparator : '',labelWidth : 70,items : [
                       {cls : 'directionsText',html : 'These overlays are time-sensitive.  Use the slider to look ahead in time.',border : false}
                      ,{html : '<img height=7 src="img/blank.png">',border : false}
                      ,new Ext.form.ComboBox({
                         store          : forecastMapsStore
                        ,id             : 'forecastMapsTypeComboBox'
                        ,listWidth      : 250
                        ,displayField   : 'id'
                        ,valueField     : 'id'
                        ,mode           : 'local'
                        ,forceSelection : true
                        ,triggerAction  : 'all'
                        ,editable       : false
                        ,value          : forecastMapsStore.getAt(forecastMapsStore.findExact('visibility',true)).get('id')
                        ,fieldLabel     : 'Type'
                        ,width          : 218
                        ,listeners      : {select : function(combo,rec) {
                          hideAllLayers();
                          if (rec) {
                            var layers = rec.get('wmsLayers');
                            for (var i = 0; i < layers.length; i++) {
                              map.getLayersByName(layers[i])[0].setVisibility(true);
                            }
                          }
                          syncMapLegends('forecastMapsTypeComboBox','forecastsLegendPanel');
                        }}
                      })
                      ,{html : '<img height=3 src="img/blank.png">',border : false}
                      ,{
                         id     : 'forecastMapsForecastText'
                        ,xtype  : 'box'
                        ,autoEl : {html : dateToFriendlyString(dNow)}
                      }
                      ,new Ext.Slider({
                         fieldLabel     : 'Time'
                        ,id             : 'fcSlider'
                        ,width          : 215
                        ,minValue       : 0
                        ,maxValue       : 48
                        ,increment      : fcSliderIncrement
                        ,value          : 0
                        ,plugins        : new Ext.slider.Tip({
                          getText : function(thumb) {
                            return String.format('{0}',dateToFriendlyString(new Date(dNow.getTime() + thumb.value * 3600000)));
                          }
                        })
                        ,listeners      : {changecomplete : function(slider,val) {
                          var sto    = Ext.getCmp('forecastMapsTypeComboBox').getStore();
                          var layers = sto.getAt(sto.findExact('id',Ext.getCmp('forecastMapsTypeComboBox').getValue())).get('wmsLayers');
                          for (var i = 0; i < layers.length; i++) {
                            var lyr = map.getLayersByName(layers[i])[0];
                            if (lyr.timeParam) {
                              lyr.mergeNewParams({TIME : makeTimeParam(new Date(dNow.getTime() + val * 3600 * 1000))});
                            }
                          }
                          Ext.getCmp('forecastMapsForecastText').update(dateToFriendlyString(new Date(dNow.getTime() + val * 3600 * 1000)));
                        }}
                      })
                      ,{html : '<img height=12 src="img/blank.png">',border : false}
                      ,new Ext.Slider({
                         fieldLabel     : 'Contrast'
                        ,id             : 'forecastsVisibilitySlider'
                        ,width          : 215
                        ,minValue       : 0
                        ,maxValue       : 100
                        ,plugins        : new Ext.slider.Tip({
                          getText : function(thumb) {
                            return String.format('{0}%',thumb.value);
                          }
                        })
                        ,listeners      : {'change' : function(slider,val) {
                          var sto    = Ext.getCmp('forecastMapsTypeComboBox').getStore();
                          var layers = sto.getAt(sto.findExact('id',Ext.getCmp('forecastMapsTypeComboBox').getValue())).get('wmsLayers');
                          for (var i = 0; i < layers.length; i++) {
                            map.getLayersByName(layers[i])[0].setOpacity(val / 100);
                          }
                        }}
                      })
                    ]}
                    ,{
                       id         : 'forecastsLegendPanel'
                      ,xtype      : 'box'
                      ,anchor     : '100% -150'
                      ,autoScroll : true
                      ,autoEl     : {html : '&nbsp;'}
                      ,style     : {
                         border     : '1px solid #99BBE8'
                        ,background : '#FFFFFF'
                      }
                      ,listeners  : {render : function(panel) {
                        if (panel.contentToLoad) {
                          panel.update(panel.contentToLoad);
                        }
                      }}
                    }
                  ]
                })
              ]
              ,listeners : {afterrender : function(p) {
                p.addListener('tabchange',function(p,tab) {
                  changeMode(tab.id.split('Panel')[0]);
                });
              }}
            })
          ]}
          ,sPanel
        ]
      }
      ,{
         region    : 'center'
        ,layout    : 'border'
        ,items     : [
          {
             region    : 'center'
            ,id        : 'mapPanel'
            ,html      : '<div id="map"><div id="mapMessagesButtonGroup"></div><div id="mapControlsResetMap"></div><div id="mapControlsChangeBackground"></div>' + (viewer == 'lite' ? '<div id="activity"><img src="img/spinner.gif"></div>' : '') + '<div id="byCatchLegend"></div></div>'
            ,border    : false
            ,listeners : {
              afterrender : function(p) {
                initMap();
              }
              ,bodyresize : function(p,w,h) {
                var el = document.getElementById('map');
                if (el) {
                  el.style.width  = w;
                  el.style.height = h;
                  map.updateSize();
                }
                var fc = Ext.getCmp('forecastWindow');
                if (fc && fc.rendered) {
                  var winH = Math.max(180,fc.getHeight());
                  fc.setPosition(0,h - winH + Number(banner.height));
                  fc.setWidth(w);
                  fc.setHeight(winH);
                }
              }
            }
          }
        ]
        ,bbar : {id : 'bbar',height : 45,hidden : viewer != 'lite',items : [
          new Ext.Panel({width : 275,hidden : true,id : 'bbarOceanConditionBbarPanel',layout : 'column',columns : 3,defaults : {border : false,bodyStyle : 'text-align:center'},bodyStyle : 'padding:6px',items : [
             new Ext.form.Label({html : '&nbsp;',id : 'bbarOceanConditionDataType'})
            ,new Ext.form.Label({html : '<img width=10 src="img/blank.png">'})
            ,new Ext.form.Label({html : '&nbsp;',id : 'bbarOceanConditionLegend'})
          ]})
          ,new Ext.form.Label({id : 'timeSpacer',html : '&nbsp;',hidden : true})
          ,new Ext.Panel({width : 295,id : 'timeControl',hidden : true,layout : 'column',columns : 5,defaults : {border : false,bodyStyle : 'text-align:center;background:#9ac6f5'},bodyStyle : 'padding:6px;background:url(img/blueGrad.jpg)',items : [
             new Ext.form.Label({html : '<table class="bbarLabels"><tr><td><img width=32 height=32 src="img/clock32.png"></td><td><img src="img/blank.png" width=5 height=5></td><td>Forecast<br>time</td></tr></table>'})
            ,new Ext.form.Label({html : '<img height=5 width=5 src="img/blank.png">'})
            ,new Ext.Button({
               icon    : 'img/ButtonLeft.png'
              ,width   : 18
              ,handler : function() {
                var cb  = Ext.getCmp('timeComboBox');
                var sto = cb.getStore();
                var idx = sto.findExact('val',cb.getValue());
                if (idx - 1 < 0) {
                  return;
                }
                cb.setValue(sto.getAt(idx - 1).get('val'));
                cb.fireEvent('select',cb,sto.getAt(idx - 1),idx - 1);
              }
            })
            ,new Ext.form.ComboBox({
              store          : new Ext.data.ArrayStore({
                 fields : ['lab','val']
              })
              ,width          : 150
              ,forceSelection : true
              ,triggerAction  : 'all'
              ,cls            : Ext.isChrome ? 'chromeInput' : ''
              ,selectOnFocus  : true
              ,mode           : 'local'
              ,displayField   : 'lab'
              ,valueField     : 'val'
              ,id             : 'timeComboBox'
              ,listeners      : {
                render : function(cb) {
                  var sto = cb.getStore();
                  for (var i = 0; i <= 48; i += fcSliderIncrement) {
                    sto.add(new sto.recordType({
                       lab : dateToFriendlyString(new Date(dNow.getTime() + i * 3600000))
                      ,val : makeTimeParam(new Date(dNow.getTime() + i * 3600000))
                    }));
                  }
                  cb.setValue(sto.getAt(0).get('val'));
                }
                ,select : function(cb,rec,idx) {
                  var sto    = Ext.getCmp('forecastMapsTypeComboBox').getStore();
                  var layers = sto.getAt(sto.findExact('id',Ext.getCmp('forecastMapsTypeComboBox').getValue())).get('wmsLayers');
                  for (var i = 0; i < layers.length; i++) {
                    var lyr = map.getLayersByName(layers[i])[0];
                    if (lyr.timeParam) {
                      lyr.mergeNewParams({TIME : rec.get('val')});
                    }
                  }
                }
              }
            })
            ,new Ext.Button({
               icon : 'img/ButtonRight.png'
              ,width   : 18
              ,handler : function() {
                var cb  = Ext.getCmp('timeComboBox');
                var sto = cb.getStore();
                var idx = sto.findExact('val',cb.getValue());
                if (idx + 1 > sto.getCount() - 1) {
                  return;
                }
                cb.setValue(sto.getAt(idx + 1).get('val'));
                cb.fireEvent('select',cb,sto.getAt(idx + 1),idx + 1);
              }
            })
          ]})
          ,new Ext.form.Label({id : 'findBuoySpacer',html : '&nbsp;',hidden : true})
          ,new Ext.Panel({id : 'findBuoyControl',width : 280,layout : 'column',columns : 3,defaults : {border : false,bodyStyle : 'text-align:center;background:#DFE8F6'},bodyStyle : 'padding:6px;background:#DFE8F6',hidden : true,items : [
             new Ext.form.Label({html : '<table class="bbarLabels"><tr><td>Find a<br>&nbsp;station&nbsp;</td></tr></table>',width : 42})
            ,new Ext.form.Label({html : '<img height=15 width=15 src="img/blank.png">',width : 17})
            ,new Ext.form.ComboBox({
               width          : 200
              ,listWidth      : 500
              ,id             : 'stationQuickFindComboBox'
              ,store          : new Ext.data.ArrayStore({
                 fields : ['lbl','provider','descr']
                ,filter : function(property,value) {
                  if (value == '') {
                    return true;
                  }
                  this.filterBy(function(record,id) {
                    return record.get('lbl').toLowerCase().indexOf(value.toLowerCase()) >= 0
                  });
                }
              })
              ,forceSelection : true
              ,triggerAction  : 'all'
              ,emptyText      : 'Enter part of a station name.'
              ,cls            : Ext.isChrome ? 'chromeInput' : ''
              ,selectOnFocus  : true
              ,mode           : 'local'
              ,displayField   : 'lbl'
              ,listeners      : {select : function(cb,rec,i) {
                var tree = Ext.getCmp('weatherStationsTreePanel');
                tree.getRootNode().cascade(function(n) {
                  if (n.attributes.provider == rec.get('provider') && n.attributes.text == rec.get('descr')) {
                    tree.selectPath(n.getPath());
                    n.ui.focus();
                    goObs('all',{provider : n.attributes.provider,descr : n.attributes.text});
                    return false;
                  }
                });
              }}
              ,doQuery : function(q, forceAll){
                q = Ext.isEmpty(q) ? '' : q;
                var qe = {
                  query: q,
                  forceAll: forceAll,
                  combo: this,
                  cancel:false
                };
                if(this.fireEvent('beforequery', qe)===false || qe.cancel){
                  return false;
                }
                q = qe.query;
                forceAll = qe.forceAll;
                if(forceAll === true || (q.length >= this.minChars)){
                  if(this.lastQuery !== q){
                    this.lastQuery = q;
                    if(this.mode == 'local'){
                      this.selectedIndex = -1;
                      if(forceAll){
                        this.store.clearFilter();
                      }else{
                        this.store.filter(this.displayField, q, true); // supply the anyMatch option
                      }
                      this.onLoad();
                    }else{
                      this.store.baseParams[this.queryParam] = q;
                      this.store.load({
                        params: this.getParams(q)
                      });
                      this.expand();
                    }
                  }else{
                    this.selectedIndex = -1;
                    this.onLoad();
                  }
                }
              }
            })
          ]})
          ,'->'
          ,{
             text     : 'Ocean<br>Conditions'
            ,id       : 'oceanConditionsButton'
            ,icon     : 'img/world32.png'
            ,scale    : 'large'
            ,width    : 110
            ,tooltip  : 'Browse ocean condition data'
            ,pressed      : true
            ,enableToggle : true
            ,allowDepress : true
            ,handler      : function(b) {
              if (b.pressed) {
                Ext.getCmp('browseOceanConditionDataWindow').show();
              }
              else {
                Ext.getCmp('browseOceanConditionDataWindow').hide();
              }
            }
          }
          ,'-'
          ,{
             text     : 'Bycatch<br>Reports'
            ,id       : 'byCatchButton'
            ,icon     : 'img/fishcatch32.png'
            ,scale    : 'large'
            ,width    : 110
            ,tooltip  : 'Browse bycatch data'
            ,pressed      : false
            ,enableToggle : true
            ,allowDepress : true
            ,pressed      : startupbyCatchLayer
            ,handler      : function(b) {
              if (b.pressed) {
                Ext.getCmp('browseByCatchDataWindow').show();
              }
              else {
                Ext.getCmp('browseByCatchDataWindow').hide();
              }
            }
          }
          ,'-'
          ,{
             text     : 'Conditions<br>Report'
            ,id       : 'conditionsReportButton'
            ,icon     : 'img/pdf32.png'
            ,scale    : 'large'
            ,width    : 100
            ,tooltip  : 'Create a conditions report'
            ,pressed      : false
            ,enableToggle : true
            ,allowDepress : true
            ,handler      : function(b) {
              if (b.pressed) {
                if (!b.initAlert) {
                  Ext.Msg.alert('Conditions report',"Click anywhere on the map to create a conditions report.");
                }
                b.initAlert = true;
                highlightControl.deactivate();
                selectControl.deactivate();
              }
              else {
                highlightControl.activate();
                selectControl.activate();
                var l = map.getLayersByName('queryPt')[0];
                l.removeFeatures(l.features);
              }
            }
          }
          ,'-'
          ,{
             text     : 'Map<br>Settings'
            ,icon     : 'img/cog32.png'
            ,scale    : 'large'
            ,tooltip  : 'Change map settings'
            ,menu     : {items : [
              {
                 text        : '<b>Select a map background</b>'
                ,canActivate : false
                ,cls         : 'menuHeader'
              }
              ,{
                 text         : 'ESRI Ocean'
                ,checked      : defaultBasemap == 'ESRI Ocean'
                ,group        : 'basemap'
                ,handler      : function() {
                  var lyr = map.getLayersByName('ESRI Ocean')[0];
                  if (lyr.isBaseLayer) {
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }
                }
              }
              ,'-'
              ,{
                 text         : 'Google Hybrid'
                ,checked      : defaultBasemap == 'Google Hybrid'
                ,group        : 'basemap'
                ,handler      : function() {
                  var lyr = map.getLayersByName('Google Hybrid')[0];
                  if (lyr.isBaseLayer) {
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }
                }
              }
              ,{
                 text         : 'Google Satellite'
                ,checked      : defaultBasemap == 'Google Satellite'
                ,group        : 'basemap'
                ,handler      : function() {
                  var lyr = map.getLayersByName('Google Satellite')[0];
                  if (lyr.isBaseLayer) {
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }
                }
              }
              ,{
                 text         : 'Google Terrain'
                ,checked      : defaultBasemap == 'Google Terrain'
                ,group        : 'basemap'
                ,handler      : function() {
                  var lyr = map.getLayersByName('Google Terrain')[0];
                  if (lyr.isBaseLayer) {
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }
                }
              }
              ,'-'
              ,{
                 text         : 'Nautical Charts'
                ,checked      : defaultBasemap == 'Nautical Charts'
                ,group        : 'basemap'
                ,handler      : function() {
                  var lyr = map.getLayersByName('Nautical Charts')[0];
                  if (lyr.isBaseLayer) {
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }
                }
              }
/*
              ,'-'
              ,{
                 text         : 'Shaded Relief (ETOPO1)'
                ,checked      : defaultBasemap == 'Shaded Relief (ETOPO1)'
                ,group        : 'basemap'
                ,handler      : function() {
                  var lyr = map.getLayersByName('Shaded Relief (ETOPO1)')[0];
                  if (lyr.isBaseLayer) {
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }
                }
              }
              ,{
                 text         : 'Shaded Relief (GEBCO_08)'
                ,checked      : defaultBasemap == 'Shaded Relief (GEBCO_08)'
                ,group        : 'basemap'
                ,handler      : function() {
                  var lyr = map.getLayersByName('Shaded Relief (GEBCO_08)')[0];
                  if (lyr.isBaseLayer) {
                    map.setBaseLayer(lyr);
                    lyr.redraw();
                  }
                }
              }
*/
              ,{
                 text        : '<b>Bathymetry options</b>'
                ,canActivate : false
                ,cls         : 'menuHeader'
              }
              ,{
                 checked  : !startupBathyContours
                ,text     : 'Hide bathymetry contour lines (m)'
                ,group    : 'bathy'
                ,handler  : function(cbox) {
                  map.getLayersByName('Bathymetry contours')[0].setVisibility(false);
                }
              }
              ,{
                 checked  : startupBathyContours
                ,text     : 'Show bathymetry contour lines (m)'
                ,group    : 'bathy'
                ,handler  : function(cbox) {
                  map.getLayersByName('Bathymetry contours')[0].setVisibility(true);
                }
              }
              ,{
                 text        : '<b>Layer contrast</b>'
                ,canActivate : false
                ,cls         : 'menuHeader'
                ,id          : 'contrastHeader'
              }
              ,new Ext.Panel({id : 'contrastSliderWrapper',bodyStyle : 'background:transparent',border : false,icon : 'img/blank.png',layout : 'column',defaults : {border : false},items : [
                 {html : '0%&nbsp;',bodyStyle : 'padding-top:6px;background:transparent'}
                ,new Ext.Slider({
                   width          : 115
                  ,id             : 'contrastSlider'
                  ,icon           : 'img/blank.png'
                  ,minValue       : 0
                  ,maxValue       : 100
                  ,plugins        : new Ext.slider.Tip({
                    getText : function(thumb) {
                      return String.format('{0}%',thumb.value);
                    }
                  })
                  ,listeners      : {'change' : function(slider,val) {
                    var layers = [];
                    if (Ext.getCmp('themeSatellite').pressed) {
                      var sto = Ext.getCmp('weatherMapsTypeComboBox').getStore();
                      layers  = sto.getAt(sto.findExact('id',Ext.getCmp('weatherMapsTypeComboBox').getValue())).get('wmsLayers');
                    }
                    else {
                      var sto = Ext.getCmp('forecastMapsTypeComboBox').getStore();
                      layers  = sto.getAt(sto.findExact('id',Ext.getCmp('forecastMapsTypeComboBox').getValue())).get('wmsLayers');
                    }
                    for (var i = 0; i < layers.length; i++) {
                      map.getLayersByName(layers[i])[0].setOpacity(val / 100);
                    }
                  }}
                })
              ,{html : '&nbsp;100%',bodyStyle : 'padding-top:6px;background:transparent'}]})
            ]}
          }
        ]}
      }
    ]
  });

  if (viewer != 'lite') {
    return;
  }

  document.getElementById('byCatchLegend').style.visibility = startupbyCatchLayer ? 'visible' : 'hidden';

  var win = new Ext.Window({
     title     : 'Browse Ocean Condition Data'
    ,id        : 'browseOceanConditionDataWindow'
    ,height    : 200 // 340
    ,width     : 370
    ,x         : Ext.getCmp('mapPanel').getWidth() - 370
    ,y         : banner.height
    ,resizable : false
    ,constrainHeader : true
    ,closeAction : 'hide'
    ,minimizable : true
    ,closable    : false
    ,defaults  : {border : false}
    ,hideNotice : false
    ,items     : {
       bodyStyle : 'padding:6px'
      ,layout    : 'anchor'
      ,items     : [
        new Ext.form.FieldSet({
           title        : '&nbsp;Select a Data Source&nbsp;'
          ,layout       : 'table'
          ,height       : 155
          ,layoutConfig : {
            columns : 3
          } 
          ,defaults : {border : false}
          ,bodyStyle : 'padding:6px'
          ,items  : [
            new Ext.Button({ 
               scale : 'large'
              ,width : 50
              ,toggleGroup  : 'themeGroup'
              ,id           : 'themeBuoys'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/buoy_icon32.png'
              ,handler : function() {
                goTheme('Buoys');
              }
              ,pressed : startupMode == 'observations'
              ,listeners : {render : function() {if (startupMode == 'observations') {goTheme('Buoys')}}}
            }) 
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink' 
              ,html : '<a href="javascript:goTheme(\'Buoys\')"><span id ="themeBuoysTitle"' + (startupMode == 'observations' ? ' style="font-weight:bold;color : #15428b"' : '') + '>BUOYS & STATIONS</span><br>View real-time data from buoy and land stations.</a>'
            }
            ,{html : '<img height=5 src="img/blank.png">',colspan : 3}
            ,new Ext.Button({
               scale : 'large'
              ,width : 50
              ,toggleGroup  : 'themeGroup'
              ,id           : 'themeSatellite'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/satellite32.png'
              ,handler : function() {
                goTheme('Satellite');
              }
              ,pressed : startupMode == 'weather'
              ,listeners : {render : function() {if (startupMode == 'weather') {goTheme('Satellite')}}}
            }) 
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goTheme(\'Satellite\')"><span id="themeSatelliteTitle"' + (startupMode == 'weather' ? ' style="font-weight:bold;color : #15428b"' : '') + '>SATELLITE & RADAR</span><br>View real-time data across the region.</a>'
            }
            ,{html : '<img height=5 src="img/blank.png">',colspan : 3}
            ,new Ext.Button({
               scale : 'large'
              ,width : 50
              ,toggleGroup  : 'themeGroup'
              ,id           : 'themeModels'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/globe32.png'
              ,handler : function() {
                goTheme('Models');
              }
              ,pressed : startupMode == 'forecasts'
              ,listeners : {render : function() {if (startupMode == 'forecasts') {goTheme('Models')}}}
            }) 
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goTheme(\'Models\')"><span id="themeModelsTitle"' + (startupMode == 'forecasts' ? ' style="font-weight:bold;color : #15428b"' : '') + '>MODEL FORECASTS</span><br>View model forecasts of ocean conditions.</a>'
            }
          ]
        })
        ,new Ext.form.FieldSet({
           title        : '&nbsp;Select a Data Type&nbsp;'
          ,id           : 'fieldSetBuoys'
          ,layout       : 'table'
          ,height       : 130
          ,layoutConfig : {
            columns : 11
          }
          ,defaults : {border : false}
          ,bodyStyle : 'padding:6px'
          ,items  : [
            new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obswinds'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'Winds'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('winds');
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'Winds') {selectWeatherStationType('winds')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'winds\')"><span id="obswindsTitle"' + (defaultObs == 'Winds' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Winds</span></a>'
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obswaves'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'Waves'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('waves');  
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'Waves') {selectWeatherStationType('waves')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'waves\')"><span id="obswavesTitle"' + (defaultObs == 'Waves' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Waves</span></a>'
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obswaterTemp'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'WaterTemp'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('waterTemp');
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'WaterTemp') {selectWeatherStationType('waterTemp')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'waterTemp\')"><span id="obswaterTempTitle"' + (defaultObs == 'WaterTemp' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Water Temp</span></a>'
            }
            ,new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obswaterLevel'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'WaterLevel'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('waterLevel');
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'WaterLevel') {selectWeatherStationType('waterLevel')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'waterLevel\')"><span id="obswaterLevelTitle"' + (defaultObs == 'WaterLevel' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Water Level</span></a>'
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obsdissolvedOxygen'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'DissolvedOxygen'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('dissolvedOxygen');
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'DissolvedOxygen') {selectWeatherStationType('dissolvedOxygen')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'dissolvedOxygen\')"><span id="obsdissolvedOxygenTitle"' + (defaultObs == 'DissolvedOxygen' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Dissolved Oxygen</span></a>'
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obsairTemperature'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'AirTemperature'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('airTemperature');
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'AirTemperature') {selectWeatherStationType('airTemperature')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'airTemperature\')"><span id="obsairTemperatureTitle"' + (defaultObs == 'AirTemperature' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Air<br>Temp</span></a>'
            }
            ,new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obsall'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'All'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('all');
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'All') {selectWeatherStationType('all')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'all\')"><span id="obsallTitle"' + (defaultObs == 'All' ? ' style="font-weight:bold;color : #15428b"' : '') + '>View All Stations</span></a>'
            }
            ,{html : '&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'obsGroup'
              ,id           : 'obsnone'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/buoy_icon16.png'
              ,scale : 'medium'
              ,pressed      : defaultObs == 'None'
              ,handler      : function(b) {
                if (b.pressed) {
                  goObs('none');
                }
              }
              ,listeners    : {render : function() {if (activeMode == 'forecasts' && defaultObs == 'None') {selectWeatherStationType('none')}}}
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goObs(\'none\')"><span id="obsnoneTitle"' + (defaultObs == 'None' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Show None</span></a>'
            }
          ]
        })
        ,new Ext.form.FieldSet({
           title        : '&nbsp;Select a Data Type&nbsp;'
          ,id           : 'fieldSetSatellite'
          ,layout       : 'table'
          ,height       : 130
          ,layoutConfig : {
            columns : 7
          }
          ,defaults : {border : false}
          ,bodyStyle : 'padding:6px'
          ,items  : [
            new Ext.Button({
               toggleGroup  : 'satelliteGroup'
              ,id           : 'satelliteChlorophyll concentration'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/satellite16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goSatellite('Chlorophyll concentration');
                }
              }
              ,pressed : Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Chlorophyll concentration'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goSatellite(\'Chlorophyll concentration\')"><span id="satelliteChlorophyll concentrationTitle"' + (Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Chlorophyll concentration' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Chlorophyll<br>Concentration</span></a> <img id="goSatelliteChlorophyll concentration" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Chlorophyll concentration'
                    ,html      : map.getLayersByName('Chlorophyll concentration')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goSatelliteChlorophyll concentration'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'satelliteGroup'
              ,id           : 'satelliteSatellite water temperature'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/satellite16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goSatellite('Satellite water temperature');
                }
              }
              ,pressed : Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Satellite water temperature'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goSatellite(\'Satellite water temperature\')"><span id="satelliteSatellite water temperatureTitle"' + (Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Satellite water temperature' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Water<br>Temperature</span></a> <img id="goSatelliteSatellite water temperature" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Water temperature'
                    ,html      : map.getLayersByName('Satellite water temperature')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goSatelliteSatellite water temperature'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,new Ext.Button({
               toggleGroup  : 'satelliteGroup'
              ,id           : 'satelliteOcean fronts'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/satellite16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goSatellite('Ocean fronts');
                }
              }
              ,pressed : Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Ocean fronts'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goSatellite(\'Ocean fronts\')"><span id="satelliteOcean frontsTitle"' + (Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Ocean fronts' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Ocean<br>Fronts</span></a> <img id="goSatelliteOcean fronts" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Ocean fronts'
                    ,html      : map.getLayersByName('Ocean fronts')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goSatelliteOcean fronts'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'satelliteGroup'
              ,id           : 'satelliteWeather RADAR and cloud imagery'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/satellite16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goSatellite('Weather RADAR and cloud imagery');
                }
              }
              ,pressed : Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Weather RADAR and cloud imagery'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goSatellite(\'Weather RADAR and cloud imagery\')"><span id="satelliteWeather RADAR and cloud imageryTitle"' + (Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'Weather RADAR and cloud imagery' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Weather RADAR<br>& Cloud Imagery</span></a> <img id="goSatelliteWeather RADAR and cloud imagery" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Weather RADAR & Cloud Imagery'
                    ,html      : map.getLayersByName('Weather RADAR')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goSatelliteWeather RADAR and cloud imagery'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,new Ext.Button({
               toggleGroup  : 'satelliteGroup'
              ,id           : 'satelliteNone'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/satellite16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goSatellite('None');
                }
              }
              ,pressed : Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'None'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goSatellite(\'None\')"><span id="satelliteNoneTitle"' + (Ext.getCmp('weatherMapsTypeComboBox').getValue() == 'None' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Show None</span></a>'
            }
          ]
        })
        ,new Ext.form.FieldSet({
           title        : '&nbsp;Select a Data Type&nbsp;'
          ,id           : 'fieldSetModels'
          ,layout       : 'table'
          ,height       : 130
          ,layoutConfig : {
            columns : 11
          }
          ,defaults : {border : false}
          ,bodyStyle : 'padding:6px'
          ,items  : [
            new Ext.Button({
               toggleGroup  : 'modelGroup'
              ,id           : 'modelWinds'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('Winds');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Winds'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'Winds\')"><span id="modelWindsTitle"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Winds' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Winds</span></a> <img id="goModelWinds" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Winds'
                    ,html      : map.getLayersByName('Winds')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goModelWinds'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'modelGroup'
              ,id           : 'modelWaves'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('Waves');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Waves'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'Waves\')"><span id="modelWavesTitle"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Waves' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Waves</span></a> <img id="goModelWaves" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Waves'
                    ,html      : map.getLayersByName('Waves')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goModelWaves'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'modelGroup' 
              ,id           : 'modelSurface water temperature'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('Surface water temperature');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Surface water temperature'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'Surface water temperature\')"><span id="modelSurface water temperatureTitle"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Surface water temperature' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Water Temp</span></a> <img id="goModelSurface water temperature" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Surface water temperature'
                    ,html      : map.getLayersByName('Surface water temperature')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goModelSurface water temperature'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,new Ext.Button({
               toggleGroup  : 'modelGroup'
              ,id           : 'modelCurrents (global)'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('Currents (global)');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Currents (global)'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'Currents (global)\')"><span id="modelCurrents (global)Title"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Currents (global)' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Global Currents</span></a> <img id="goModelCurrents (global)" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Currents (global)'
                    ,html      : map.getLayersByName('Currents (global)')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goModelCurrents (global)'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'modelGroup'
              ,id           : 'modelCurrents (regional)'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('Currents (regional)');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Currents (regional)'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'Currents (regional)\')"><span id="modelCurrents (regional)Title"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Currents (regional)' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Regional Currents</span></a> <img id="goModelCurrents (regional)" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Currents (regional)'
                    ,html      : map.getLayersByName('Currents (regional)')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goModelCurrents (regional)'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '&nbsp;&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'modelGroup'
              ,id           : 'modelBottom water temperature'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('Bottom water temperature');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Bottom water temperature'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'Bottom water temperature\')"><span id="modelBottom water temperatureTitle"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Bottom water temperature' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Bottom Temp</span></a> <img id="goModelBottom water temperature" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Bottom water temperature'
                    ,html      : map.getLayersByName('Bottom water temperature')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goModelBottom water temperature'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,new Ext.Button({
               toggleGroup  : 'modelGroup'
              ,id           : 'modelCurrents (New York Harbor)'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('Currents (New York Harbor)');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Currents (New York Harbor)'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'Currents (New York Harbor)\')"><span id="modelCurrents (New York Harbor)Title"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'Currents (New York Harbor)' ? ' style="font-weight:bold;color : #15428b"' : '') + '>NY Harbor<br>Currents</span></a> <img id="goModelCurrents (New York Harbor)" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Currents (New York Harbor)'
                    ,html      : map.getLayersByName('Currents (New York Harbor)')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goModelCurrents (New York Harbor)'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '&nbsp;'}
            ,new Ext.Button({
               toggleGroup  : 'modelGroup'
              ,id           : 'modelNone'
              ,enableToggle : true
              ,allowDepress : false
              ,icon : 'img/globe16.png'
              ,scale : 'medium'
              ,handler      : function(b) {
                if (b.pressed) {
                  goModel('None');
                }
              }
              ,pressed : Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'None'
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goModel(\'None\')"><span id="modelNoneTitle"' + (Ext.getCmp('forecastMapsTypeComboBox').getValue() == 'None' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Show None</span></a>'
            }
          ]
        })
      ]
    }
    ,listeners : {minimize : function(w) {w.hide()},hide : function(w) {
      if (!w.hideNotice) {
        Ext.Msg.alert('Map controls',"This window can be reactivated by clicking on the Ocean Conditions button in the toolbar below the map.");
        w.hideNotice = true;
      }
      Ext.getCmp('oceanConditionsButton').toggle(false);
    }}
  });
  win.show();

  win = new Ext.Window({
     title     : 'Browse Bycatch Reports'
    ,id        : 'browseByCatchDataWindow'
    ,height    : 255
    ,width     : 370
    ,x         : Ext.getCmp('mapPanel').getWidth() - 370
    ,y         : Number(banner.height) + 1 + 340
    ,resizable : false
    ,minimizable : true
    ,closable    : false
    ,constrainHeader : true
    ,closeAction : 'hide'
    ,defaults  : {border : false}
    ,hideNotice : false
    ,items     : {
       bodyStyle : 'padding:6px'
      ,layout    : 'anchor'
      ,items     : [
        new Ext.form.FieldSet({
           title        : '&nbsp;Select a Data Type&nbsp;'
          ,layout       : 'table'
          ,height       : 210
          ,layoutConfig : {
            columns : 7
          }
          ,defaults : {border : false}
          ,bodyStyle : 'padding:6px'
          ,items  : [
            new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchBottom trawl Northeast/MA'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : startupbyCatchLayer == 'Bottom trawl Northeast/MA'
              ,handler      : function() {
                goByCatch('Bottom trawl Northeast/MA');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'Bottom trawl Northeast/MA\')"><span id="byCatchBottom trawl Northeast/MATitle"' + (startupbyCatchLayer == 'Bottom trawl Northeast/MA' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Butterfish</span><br>Bottom trawl<br>Northeast/MA</a> <img id="goByCatchBottom trawl Northeast/MA" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Bottom trawl Northeast/MA'
                    ,html      : map.getLayersByName('Butterfish bottom trawl')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goByCatchBottom trawl Northeast/MA'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '<img height=25 src="img/blank.png">'}
            ,new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchBottom trawl Rhode Island'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : startupbyCatchLayer == 'Bottom trawl Rhode Island'
              ,handler      : function() {
                goByCatch('Bottom trawl Rhode Island');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'Bottom trawl Rhode Island\')"><span id="byCatchBottom trawl Rhode IslandTitle"' + (startupbyCatchLayer == 'Bottom trawl Rhode Island' ? ' style="font-weight:bold;color : #15428b"' : '') + '>River herring</span><br>Bottom trawl<br>Rhode Island</a> <img id="goByCatchBottom trawl Rhode Island" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Bottom trawl Rhode Island'
                    ,html      : map.getLayersByName('River herring bottom trawl')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goByCatchBottom trawl Rhode Island'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchMid-water trawl Area 2'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : startupbyCatchLayer == 'Mid-water trawl Area 2'
              ,handler      : function() {
                goByCatch('Mid-water trawl Area 2');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'Mid-water trawl Area 2\')"><span id="byCatchMid-water trawl Area 2Title"' + (startupbyCatchLayer == 'Mid-water trawl Area 2' ? ' style="font-weight:bold;color : #15428b"' : '') + '>River herring</span><br>Mid-water trawl<br>Area 2</a> <img id="goByCatchMid-water trawl Area 2" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Mid-water trawl Area 2'
                    ,html      : map.getLayersByName('River herring mid-water trawl Area 2')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goByCatchMid-water trawl Area 2'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '<img height=25 src="img/blank.png">'}
            ,new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchMid-water trawl Cape Cod'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : startupbyCatchLayer == 'Mid-water trawl Cape Cod'
              ,handler      : function() {
                goByCatch('Mid-water trawl Cape Cod');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'Mid-water trawl Cape Cod\')"><span id="byCatchMid-water trawl Cape CodTitle"' + (startupbyCatchLayer == 'Mid-water trawl Cape Cod' ? ' style="font-weight:bold;color : #15428b"' : '') + '>River herring</span><br>Mid-water trawl<br>Cape Cod</a> <img id="goByCatchMid-water trawl Cape Cod" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Mid-water trawl Cape Cod'
                    ,html      : map.getLayersByName('River herring mid-water trawl Cape Cod')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goByCatchMid-water trawl Cape Cod'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchClosed area 1 Georges Bank'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : startupbyCatchLayer == 'Closed area 1 Georges Bank'
              ,handler      : function() {
                goByCatch('Closed area 1 Georges Bank');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'Closed area 1 Georges Bank\')"><span id="byCatchClosed area 1 Georges BankTitle"' + (startupbyCatchLayer == 'Closed area 1 Georges Bank' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Scallop/yellowtail</span><br>Closed Area 1<br>Georges Bank</a> <img id="goByCatchClosed area 1 Georges Bank" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Closed area 1 Georges Bank'
                    ,html      : map.getLayersByName('Scallop/yellowtail closed area 1')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goByCatchClosed area 1 Georges Bank'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '<img height=25 src="img/blank.png">'}
            ,new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchClosed area 2 Georges Bank'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : startupbyCatchLayer == 'Closed area 2 Georges Bank'
              ,handler      : function() {
                goByCatch('Closed area 2 Georges Bank');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'Closed area 2 Georges Bank\')"><span id="byCatchClosed area 2 Georges BankTitle"' + (startupbyCatchLayer == 'Closed area 2 Georges Bank' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Scallop/yellowtail</span><br>Closed Area 2<br>Georges Bank</a> <img id="goByCatchClosed area 2 Georges Bank" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Closed area 2 Georges Bank'
                    ,html      : map.getLayersByName('Scallop/yellowtail closed area 2')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goByCatchClosed area 2 Georges Bank'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchNantucket Lightship'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : startupbyCatchLayer == 'Nantucket Lightship'
              ,handler      : function() {
                goByCatch('Nantucket Lightship');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'Nantucket Lightship\')"><span id="byCatchNantucket LightshipTitle"' + (startupbyCatchLayer == 'Nantucket Lightship' ? ' style="font-weight:bold;color : #15428b"' : '') + '>Scallop/yellowtail</span><br>Nantucket Lightship</a> <img id="goByCatchNantucket Lightship" width=10 height=10 src="img/small-help-icon.gif">'
              ,listeners : {
                afterrender : function() {
                  new Ext.ToolTip({
                     title     : 'Nantucket Lightship'
                    ,html      : map.getLayersByName('Scallop/yellowtail Nantucket Lightship')[0].moreInfo.split('For more info')[0]
                    ,target    : 'goByCatchNantucket Lightship'
                    ,showDelay : 0
                    ,anchor    : 'right'
                    ,dismissDelay : 0
                  });
                }
              }
            }
            ,{html : '<img height=25 src="img/blank.png">'}
            ,new Ext.Button({
               toggleGroup  : 'byCatchGroup'
              ,id           : 'byCatchNone'
              ,enableToggle : true
              ,allowDepress : false
              ,icon  : 'img/fishCatch16.png'
              ,scale : 'medium'
              ,pressed      : !startupbyCatchLayer || startupbyCatchLayer == 'None'
              ,handler      : function() {
                goByCatch('None');
              }
            })
            ,{html : '&nbsp;'}
            ,{
               cls  : 'directionsTextNoAlign grayLink'
              ,html : '<a href="javascript:goByCatch(\'None\')"><span id="byCatchNoneTitle"' + (!startupbyCatchLayer || startupbyCatchLayer == 'None' ? ' style="font-weight:bold;color : #15428b"' : '') + '>None</span><br>Turn OFF bycatch</a>'
            }
          ]
        })
      ]
    }
    ,listeners : {minimize : function(w) {w.hide()},hide : function(w) {
      if (!w.hideNotice) {
        Ext.Msg.alert('Map controls',"This window can be reactivated by clicking on the Bycatch Reports button in the toolbar below the map.");
        w.hideNotice = true;
      }
      Ext.getCmp('byCatchButton').toggle(false);
    }}
  });
  if (startupbyCatchLayer) {
    win.show();
  }
}

function initMap() {
  OpenLayers.Projection.addTransform("EPSG:4326","EPSG:3857",OpenLayers.Layer.SphericalMercator.projectForward);
  OpenLayers.Projection.addTransform("EPSG:3857","EPSG:4326",OpenLayers.Layer.SphericalMercator.projectInverse);

  OpenLayers.Util.onImageLoadError = function() {this.src = 'img/blank.png';}

  map = new OpenLayers.Map('map',{
    layers            : [
      new OpenLayers.Layer.XYZ(
         'ESRI Ocean'
        ,'http://services.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/${z}/${y}/${x}.jpg'
        ,{
           sphericalMercator : true
          ,isBaseLayer       : true
          ,wrapDateLine      : true
        }
      )
      ,new OpenLayers.Layer.ArcGIS93Rest(
         'Nautical Charts'
        ,'http://egisws02.nos.noaa.gov/ArcGIS/rest/services/RNC/NOAA_RNC/MapServer/export'
        ,{
          layers : 'show:3'
        }
        ,{
          isBaseLayer : true
        }
      )
      ,new OpenLayers.Layer.ArcGIS93Rest(
         'Shaded Relief (ETOPO1)'
        ,'http://maps.ngdc.noaa.gov/arcgis/rest/services/web_mercator/etopo1_hillshade/MapServer/export'
        ,{
          layers : 'show:0'
        }
        ,{
          isBaseLayer : true
        }
      )
      ,new OpenLayers.Layer.ArcGIS93Rest(
         'Shaded Relief (GEBCO_08)'
        ,'http://maps.ngdc.noaa.gov/arcgis/rest/services/web_mercator/gebco08_hillshade/MapServer/export'
        ,{
          layers : 'show:0'
        }
        ,{
          isBaseLayer : true
        }
      )
      ,new OpenLayers.Layer.OSM(
         'OpenStreetMapOlay'
        ,'http://tile.openstreetmap.org/${z}/${x}/${y}.png'
        ,{
           isBaseLayer : false
          ,visibility  : false
        }
      )
      ,new OpenLayers.Layer.CloudMade('CloudMade',{
         key     : '9de23856dd4e449e99f298e9ae605a40'
        ,styleId : 998
        ,opacity : 0.6
      })
      ,new OpenLayers.Layer.OSM(
         'OpenStreetMap'
        ,'http://tile.openstreetmap.org/${z}/${x}/${y}.png'
      )
      ,new OpenLayers.Layer.Google('Google Satellite',{
         type          : google.maps.MapTypeId.SATELLITE
        ,projection    : proj900913
      })
      ,new OpenLayers.Layer.Google('Google Hybrid',{
         type          : google.maps.MapTypeId.HYBRID
        ,projection    : proj900913
      })
      ,new OpenLayers.Layer.Google('Google Map',{
         type          : google.maps.MapTypeId.MAP
        ,projection    : proj900913
      })
      ,new OpenLayers.Layer.Google('Google Terrain',{
         type          : google.maps.MapTypeId.TERRAIN
        ,projection    : proj900913
      })
      ,new OpenLayers.Layer.Vector(
         'watermark'
        ,{styleMap : new OpenLayers.StyleMap({
          'default' : new OpenLayers.Style(
            OpenLayers.Util.applyDefaults({
               label             : '${getLabel}'
              ,labelOutlineColor : 'white'
              ,labelOutlineWidth : 5
              ,labelYOffset      : 150
              ,fontFamily        : 'tahoma,helvetica,sans-serif' 
              ,fontColor         : '#2D9CD7'
              ,fontSize          : 18
            })
            ,{context : {getLabel : function(f) {
              return f.attributes.label ? f.attributes.label : '';
            }}}
          )
        })}
      )
      ,new OpenLayers.Layer.Vector(
         'queryPt'
        ,{styleMap : new OpenLayers.StyleMap({
          'default' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
             externalGraphic : 'img/Delete-icon.png'
            ,pointRadius     : 10
            ,graphicOpacity  : 1
            ,graphicWidth    : 16
            ,graphicHeight   : 16
          }))
        })}
      )
    ]
    ,projection        : proj900913
    ,displayProjection : proj4326
    ,units             : 'm'
    ,maxExtent         : new OpenLayers.Bounds(-20037508,-20037508,20037508,20037508.34)
    ,controls          : [
       new OpenLayers.Control.Zoom()
      ,new OpenLayers.Control.Attribution()
      ,new OpenLayers.Control.Graticule({
         labelFormat     : 'dms'
        ,layerName       : 'grid'
        ,labelSymbolizer : {
           fontColor   : "#666"
          ,fontSize    : "10px"
          ,fontFamily  : "tahoma,helvetica,sans-serif"
        }
        ,lineSymbolizer  : {
           strokeWidth     : 0.40
          ,strokeOpacity   : 0.90
          ,strokeColor     : "#999999"
          ,strokeDashstyle : "dash"
        }
      })
    ]
  });

  var mousePosCtl = new OpenLayers.Control.MousePosition({
     displayProjection : proj4326
    ,formatOutput      : function(lonlat) {
      return convertDMS(lonlat.lat.toFixed(5), "LAT") + ' ' + convertDMS(lonlat.lon.toFixed(5), "LON");
    }
  });
  map.addControl(mousePosCtl);
  mousePosCtl.element.innerHTML = convertDMS(0, "LAT") + ' ' + convertDMS(0, "LON");
  if (viewer != 'lite') {
    mousePosCtl.element.style.top = '28px';
  }
  else {
    mousePosCtl.element.style.bottom = '22px';
  }

  mapLayersStore.each(function(rec) {
    var lyr;
    if (rec.get('type') == 'wms') {
      lyr = addWMS(rec);
    }
    else if (rec.get('type') == 'wunderground') {
      lyr = addWunderground(rec);
    }
    else if (rec.get('type') == 'tilecache') {
      lyr = addTileCache(rec);
      lyr.events.register('tileerror',this,function(tile) {
        if (map.getZoom() > 9) {
          OpenLayers.Element.removeClass(tile.tile.imgDiv,'olImageLoadError');
          tile.tile.setImgSrc('img/noDataZoomOut.png');
        }
      });
    }

    lyr.events.register('loadstart',this,function(e) {
      mapLoadstartMask(e.object.name,e.object.panel);
      _gaq.push([
         '_trackEvent'
        ,e.object.panel == 'weather' ? (weatherTab + ' observations') : 'Model forecasts'
        ,e.object.name
      ]);
    });

    lyr.events.register('loadend',this,function(e) {
      mapLoadendUnmask(e.object.name,e.object.panel);
    });

    map.events.register('addlayer',this,function(e) {
      map.setLayerIndex(map.getLayersByName('watermark')[0],map.layers.length - 1);
      map.setLayerIndex(map.getLayersByName('queryPt')[0],map.layers.length - 1);
    });

    lyr.events.register('visibilitychanged',this,function(e) {
      if (e.object.visibility) {
        Ext.defer(function() {
          var slider = viewer == 'lite' ? Ext.getCmp('contrastSlider') : Ext.getCmp(e.object.panel + 'VisibilitySlider');
          if (slider) {
            slider.suspendEvents();
            slider.setValue(e.object.opacity * 100);
            slider.syncThumb();
            slider.resumeEvents();
          }
        },1000);
      }
      else {
        mapLoadendUnmask(e.object.name,e.object.panel);
      }
      syncWatermark();
    });

    if (rec.get('timeParam')) {
      lyr.mergeNewParams({TIME : makeTimeParam(dNow)});
    }
    lyr.timeParam = rec.get('timeParam');

    map.addLayer(lyr);
  });

  map.addLayer(addTMS(new mapLayersStore.recordType({
     id           : 'Watches, warnings, and advisories'
    ,getMapUrl    :
      [
         'http://ridgewms.srh.noaa.gov/tc/tc.py/'
      ]
    ,getMapLayers : 'threat'
    ,opacity      : 0.75
    ,visibility   : startupWWA
  })));

  map.addLayer(addWMS(new mapLayersStore.recordType({
     id           : 'NHC storm tracks'
    ,getMapUrl    : 'http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/wwa?BGCOLOR=0xCCCCFE&'
    ,getMapLayers : 'NHC_TRACK_POLY,NHC_TRACK_LIN,NHC_TRACK_PT,NHC_TRACK_PT_72DATE,NHC_TRACK_PT_120DATE,NHC_TRACK_PT_0NAMEDATE,NHC_TRACK_PT_MSLPLABELS,NHC_TRACK_PT_72WLBL,NHC_TRACK_PT_120WLBL,NHC_TRACK_PT_72CAT,NHC_TRACK_PT_120CAT'
    ,styles       : ''
    ,format       : 'image/png'
    ,opacity      : 0.7
    ,visibility   : startupWWA
    ,singleTile   : true
    ,moreInfo     : ''
    ,bbox         : false
    ,legend       : false
  })));

  map.addLayer(addWMS(new mapLayersStore.recordType({
     id           : 'Marine zones'
    ,getMapUrl    : 'http://db1.charthorizon.com/races-cgi-bin/mapserv?map=/home/map/mapper/prod/htdocs/nws/zones.map&'
    ,getMapLayers : 'hz,mz'
    ,styles       : ''
    ,format       : 'image/png'
    ,opacity      : 1
    ,visibility   : startupWWA
    ,singleTile   : true
    ,moreInfo     : ''
    ,bbox         : false
    ,legend       : false
  })));

  map.addLayer(addWMS(new mapLayersStore.recordType({
     id           : 'Bathymetry contours'
    ,getMapUrl    : 'http://gis.asascience.com/arcgis/services/MaracoosContours/MapServer/WMSServer'
    ,getMapLayers : '0'
    ,styles       : ''
    ,format       : 'image/png'
    ,opacity      : 1
    ,visibility   : bathyContours && startupBathyContours
    ,singleTile   : true
    ,moreInfo     : ''
    ,bbox         : false
    ,legend       : false
  })));

  map.events.register('changebaselayer',this,function() {
    if (new RegExp(/Google/).test(map.baseLayer.name)) {
      // get rid of the stupid google error link -- css won't control this!!!
      var divs = document.getElementsByTagName('div');
      for (var i = 0; i < divs.length; i++) {
        if (new RegExp(/^gmnoprint$/).test(divs[i].className)) {
          divs[i].id = 'googleMapErrorLink';
        }
      }
    }
  });

  map.setBaseLayer(map.getLayersByName(defaultBasemap)[0]);
  map.setCenter(new OpenLayers.LonLat(startupCenter[0],startupCenter[1]).transform(proj4326,proj900913),startupZoom);

  map.events.register('moveend',this,function() {
    if (navToolbarControl.controls[1].active) {
      navToolbarControl.controls[1].deactivate();
      navToolbarControl.controls[0].activate();
      navToolbarControl.draw();
    }

    if (Ext.getCmp('startupToolTip')) {
      Ext.getCmp('startupToolTip').hide();
    }
    if (lastHighlight) {
      if (lastHighlight.toolTip) {
        lastHighlight.toolTip.hide();
      }
      if (lastHighlight.feature && lastHighlight.feature.layer) {
        highlightControl.unhighlight(lastHighlight.feature);
      }
    }

    if (selectPopup && !selectPopup.isDestroyed) {
      selectPopup.hide();
    }

    var lyr = map.getLayersByName('icon')[0];
    if (lyr && lyr.unfilteredFeatures.length > 0 && lyr.listenForMoveEnd) {
      syncIconLayerWithData(lyr);
    }
    lyr.listenForMoveEnd = true;

    map.getLayersByName('OpenStreetMapOlay')[0].setVisibility(map.baseLayer.name == 'ESRI Ocean' && map.getZoom() >= 11);

    syncWatermark();

    if (Ext.getCmp('searchResultsGridPanel') && new RegExp(/Explorer boundaries/).test(Ext.getCmp('restrictGeoSearchButton').getText())) {
      runQuery();
    }

    if (userId) {
      OpenLayers.Request.issue({
        url : 'session.php?userId=' + encodeURIComponent(userId) + '&permalink=' + encodeURIComponent(linkMap(true))
      });
    }
  });

  Ext.defer(function() {
    map.events.register('changelayer',this,function(e) {
      if (userId && e.property == 'visibility') {
        OpenLayers.Request.issue({
          url : 'session.php?userId=' + encodeURIComponent(userId) + '&permalink=' + encodeURIComponent(linkMap(true))
        });
      }
    });
  },1000);

  map.events.register('click',this,function(e) {
    if (viewer == 'lite' && Ext.getCmp('conditionsReportButton').pressed) {
      mapClick(e.xy,true);
      Ext.getCmp('conditionsReportButton').toggle(false);
      highlightControl.activate();
      selectControl.activate();
    }
    else if (viewer != 'lite' && (activeMode == 'forecasts' || (activeMode == 'weather' && !Ext.getCmp('wwaLegendPanel').disabled))) {
      mapClick(e.xy);
    }
  });

  var navToolbarControl = new OpenLayers.Control.NavToolbar();
  map.addControl(navToolbarControl);
  navToolbarControl.controls[0].disableZoomBox();

  navToolbarControl.controls[1].events.register('activate',this,function(e) {
    highlightControl.deactivate();
    selectControl.deactivate();
  });
  navToolbarControl.controls[1].events.register('deactivate',this,function(e) {
    highlightControl.activate();
    selectControl.activate();
  });

  if (viewer != 'lite') {
    new Ext.Button({
       text     : 'Reset zoom'
      ,renderTo : 'mapControlsResetMap'
      ,width    : 95
      ,height   : 26
      ,icon     : 'img/zoom_extend16.png'
      ,tooltip  : 'Reset the map to its original zoom'
      ,handler  : function() {
        map.setCenter(new OpenLayers.LonLat(center[0],center[1]).transform(proj4326,proj900913),zoom);
      }
    });

    new Ext.Button({
       text     : 'Background'
      ,renderTo : 'mapControlsChangeBackground'
      ,width    : 95
      ,height   : 26
      ,icon     : 'img/map16.png'
      ,tooltip  : 'Select a different map background'
      ,menu     : {items : [
        {
           text         : 'CloudMade'
          ,checked      : defaultBasemap == 'CloudMade'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('CloudMade')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,'-'
        ,{
           text         : 'ESRI Ocean'
          ,checked      : defaultBasemap == 'ESRI Ocean'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('ESRI Ocean')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,'-'
        ,{
           text         : 'Google Hybrid'
          ,checked      : defaultBasemap == 'Google Hybrid'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('Google Hybrid')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,{
           text         : 'Google Satellite'
          ,checked      : defaultBasemap == 'Google Satellite'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('Google Satellite')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,{
           text         : 'Google Terrain'
          ,checked      : defaultBasemap == 'Google Terrain'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('Google Terrain')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,'-'
        ,{
           text         : 'Nautical Charts'
          ,checked      : defaultBasemap == 'Nautical Charts'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('Nautical Charts')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,'-'
        ,{
           text         : 'OpenStreetMap'
          ,checked      : defaultBasemap == 'OpenStreetMap'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('OpenStreetMap')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,'-'
        ,{
           text         : 'Shaded Relief (ETOPO1)'
          ,checked      : defaultBasemap == 'Shaded Relief (ETOPO1)'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('Shaded Relief (ETOPO1)')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
        ,{
           text         : 'Shaded Relief (GEBCO_08)'
          ,checked      : defaultBasemap == 'Shaded Relief (GEBCO_08)'
          ,group        : 'basemap'
          ,handler      : function() {
            var lyr = map.getLayersByName('Shaded Relief (GEBCO_08)')[0];
            if (lyr.isBaseLayer) {
              map.setBaseLayer(lyr);
              lyr.redraw();
            }
          }
        }
      ].concat(bathyContours ? [
        '-'
        ,new Ext.form.Checkbox({
           checked  : startupBathyContours
          ,boxLabel : '&nbsp;&nbsp;&nbsp;Bathymetry contours (m)'
          ,handler  : function(cbox) {
            map.getLayersByName('Bathymetry contours')[0].setVisibility(cbox.checked);
          }
        })
      ] : [])}
    });
  }

  new Ext.ButtonGroup({
     renderTo  : 'mapMessagesButtonGroup'
    ,columns   : 1
    ,autoWidth : true
    ,title     : 'Map messages'
    ,items     : {width : 250,xtype : 'container',autoEl : {tag : 'center'},items : {border : false,id : 'mapMessagesHtml',html : 'Retrieving observations...'}}
  });

/*
  // read in buffer
  var features = new OpenLayers.Format.GeoJSON().read(geo.bufferJSON);
  for (var i = 0; i < features.length; i++) {
    features[i].geometry.transform(proj4326,proj900913);
  }
  var v = new OpenLayers.Layer.Vector();
  v.addFeatures(features);
  map.addLayer(v);
*/

  getIconData(
     createIconLayer()
    ,new OpenLayers.Format.GeoJSON().read(geo.bufferJSON)[0].geometry.transform(proj4326,proj900913)
  );

  setZoomAlertOpacity();

  Ext.defer(function() {
    changeMode(startupMode);
    if (!new RegExp(/^(Winds|Waves|WaterTemp|WaterLevel|All)$/).test(defaultObs)) {
      for (var o in otherObs) {
        if (otherObs[o]['topObsName'] == defaultObs) {
          selectWeatherStationType(o);
        }
      }
    }
    eval(extraInitJS);
  },100);

  var fcWin = new Ext.Window({
     id        : 'forecastWindow'
    ,border    : false
    ,layout    : 'fit'
    ,title     : 'Forecast data'
    ,closable  : false
    ,draggable : false
    ,closeAction : 'hide'
    ,items     : {
       id       : 'forecastPanel'
      ,layout   : 'fit'
      ,defaults : {autoScroll : true}
      ,items    : {html : '<img src="img/blank.png" height=4><br>Click anywhere on the map to see a forecast.',bodyStyle : 'padding:6px',border : false,cls : 'forecastData'}
    }
    ,listeners : {
      show : function(p) {
        var mp = Ext.getCmp('mapPanel');
        p.setPosition(0,mp.getHeight() - 180 + Number(banner.height));
        p.setWidth(mp.getWidth());
        p.setHeight(180);
      }
    }
  });

  if (viewer != 'lite') {
    syncMapLegends('forecastMapsTypeComboBox','forecastsLegendPanel');
    syncMapLegends('weatherMapsTypeComboBox','weatherLegendPanel');
    syncMapLegends('byCatchMapsTypeComboBox','showByCatchLegendPanel');
  }

  Ext.getCmp('forecastWindow').addListener('resize',function(win,w,h) {
    var mp = Ext.getCmp('mapPanel');
    if (mp.getWidth() != w) {
      win.setWidth(mp.getWidth());
    };
  });

  // Chrome seems to need a push
  if (Ext.isChrome) {
    map.updateSize();
  }
}

function setZoomAlertOpacity() {
  if (zoomAlert.hits >= 1) {
    setTimeout('setZoomAlertOpacity()',100);
    return;
  }
  var el = document.getElementById('mapMessagesButtonGroup');
  el.style.opacity = zoomAlert.opacity;
  el.style.filter  = 'alpha(opacity=' + (zoomAlert.opacity * 100) + ')';
  if (zoomAlert.fading) {
    zoomAlert.opacity = zoomAlert.opacity - 0.1;
  }
  else {
    zoomAlert.opacity = zoomAlert.opacity + 0.1;
  }
  if (zoomAlert.opacity <= 0) {
    zoomAlert.fading = false;
  }
  else if (zoomAlert.opacity >= 1) {
    zoomAlert.fading = true;
    zoomAlert.hits++;
  }
  setTimeout('setZoomAlertOpacity()',100);
}

function hideAllLayers() {
  // don't do anything to byCatch layers
  var byCatch = {};
  byCatchMapsStore.each(function(rec) {
    for (var i = 0; i < rec.get('wmsLayers').length; i++) {
      byCatch[rec.get('wmsLayers')[i]] = true;
    }
  });

  mapLayersStore.each(function(rec) {
    if (!byCatch[rec.get('id')]) {
      map.getLayersByName(rec.get('id'))[0].setVisibility(false);
    }
  });
}

function hideAllByCatchLayers() {
  byCatchMapsStore.each(function(rec) {
    for (var i = 0; i < rec.get('wmsLayers').length; i++) {
      map.getLayersByName(rec.get('wmsLayers')[i])[0].setVisibility(false);
    }
  });
}

function makeTimeParam(d) {
  return d.getUTCFullYear() + '-' + String.leftPad(d.getUTCMonth() + 1,2,'0') + '-' + String.leftPad(d.getUTCDate(),2,'0') + 'T' + String.leftPad(d.getUTCHours(),2,'0') + ':00:00'
}

function createIconLayer() {
  var lyr = new OpenLayers.Layer.Vector('icon',{
    styleMap   : new OpenLayers.StyleMap({
      'default' : new OpenLayers.Style(
        OpenLayers.Util.applyDefaults(
          {
             externalGraphic : "${getExternalGraphic}"
            ,graphicOpacity  : 1
            ,graphicWidth    : "${getGraphicWidth}"
            ,graphicHeight   : "${getGraphicHeight}"
            ,pointRadius     : "${getPointRadius}"
            ,display         : "${getDisplay}"
            // native
            ,fillColor       : '#e8bb99'
            ,fillOpacity     : 0.7
            ,strokeWidth     : 1
            ,strokeColor     : '#b56529'
            ,strokeOpacity   : 1
          }
        )
        ,{
          context : iconContext(0.6,'default')
        }
      )
      ,'temporary' : new OpenLayers.Style(
        OpenLayers.Util.applyDefaults(
          {
             externalGraphic : "${getExternalGraphic}"
            ,graphicOpacity  : 1
            ,graphicWidth    : "${getGraphicWidth}"
            ,graphicHeight   : "${getGraphicHeight}"
            ,pointRadius     : "${getPointRadius}"
            ,display         : "${getDisplay}"
            // native
            ,fillColor       : '#99e9ae'
            ,fillOpacity     : 0.7
            ,strokeWidth     : 1
            ,strokeColor     : '#1d8538'
            ,strokeOpacity   : 1
          }
        )
        ,{
          context : iconContext(1,'highlight')
        }
      )
      ,'select' : new OpenLayers.Style(
        OpenLayers.Util.applyDefaults(
          {
             externalGraphic : "${getExternalGraphic}"
            ,graphicOpacity  : 1
            ,graphicWidth    : "${getGraphicWidth}"
            ,graphicHeight   : "${getGraphicHeight}"
            ,pointRadius     : "${getPointRadius}"
            ,display         : "${getDisplay}"
            // native
            ,fillColor       : '#99BBE8'
            ,fillOpacity     : 0.7
            ,strokeWidth     : 1
            ,strokeColor     : '#1558BB'
            ,strokeOpacity   : 1
          }
        )
        ,{
          context : iconContext(1,'select')
        }
      )
    })
  });
  lyr.listenForMoveEnd = true;

  lyr.events.register('visibilitychanged',this,function(e) {
    checkZoomAlert(lyr);
  });

  map.addLayer(lyr);
  addToAssetsControl([lyr]);
  return lyr;
}

function getIconData(lyr,buffer) {
  lyr.unfilteredFeatures = [];
  OpenLayers.Request.issue({
     url      : 'getTopObs.php?d=' + new Date().getTime()
    ,callback : function(r) {
      var data = [];
      var treeViewData = {};
      var sto = viewer == 'lite' ? Ext.getCmp('stationQuickFindComboBox').getStore() : Ext.getCmp('weatherStationsQuickFindComboBox').getStore();
      var json = new OpenLayers.Format.JSON().read(r.responseText);
      var recs = [];
      for (var i = 0; i < json.length; i++) {
        var geojson = new OpenLayers.Format.GeoJSON();
        var f       = geojson.read(json[i])[0];
        f.geometry.transform(proj4326,proj900913);
        var hits = 0;
        for (var o in f.attributes.timeSeries) {
          hits++;
        }
        if (hits > 0) {
          if (buffer.intersects(f.geometry)) {
            lyr.unfilteredFeatures.push(f);
            if (!treeViewData[f.attributes.provider]) {
              treeViewData[f.attributes.provider] = {
                 text     : f.attributes.provider
                ,children : []
              };
            }
            treeViewData[f.attributes.provider].children.push({
               text     : f.attributes.descr
              ,leaf     : true
              ,provider : f.attributes.provider
            });
            recs.push(new sto.recordType({
               'lbl'      : f.attributes.provider + ' Station ' + f.attributes.descr
              ,'descr'    : f.attributes.descr
              ,'provider' : f.attributes.provider
            }));
          }
        }
      }
      if (recs.length > 0) {
        sto.add(recs);
        sto.sort('lbl','ASC');
      }
      var providers = [];
      for (var i in treeViewData) {
        providers.push(i);
      }
      providers.sort();
      var children = [];
      for (var i = 0; i < providers.length; i++) {
        treeViewData[providers[i]].children.sort(function(a,b) {
          var x = a.text.toLowerCase();
          var y = b.text.toLowerCase();
          return ((x < y) ? -1 : ((x > y) ? 1 : 0));
        });
        children.push(treeViewData[providers[i]]);
      }
      Ext.getCmp('weatherStationsTreePanel').setRootNode(new Ext.tree.AsyncTreeNode({
         expanded : true
        ,leaf     : false
        ,text     : 'Tree root'
        ,children : children
      }));

/*
      var f = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(-72,40).transform(proj4326,proj900913));
      f.attributes.provider   = 'MyObs';
      f.attributes.descr      = 'Look at me';
      f.attributes.timeSeries = {};
      f.attributes.topObs     = {'MyObs' : {t : new Date().getTime() / 1000}};
      lyr.unfilteredFeatures.push(f);
*/
      syncIconLayerWithData(lyr);
    }
  });
}

function syncIconLayerWithData(lyr,guaranteeFeature) {
  if (!guaranteeFeature && lyr.bbox && lyr.bbox.containsBounds(map.getExtent()) && lyr.zoom && lyr.zoom == map.getZoom()) {
    return;
  }
  lyr.bbox = new OpenLayers.Geometry.LinearRing(map.getExtent().toGeometry().getVertices()).resize(2,new OpenLayers.Geometry.Point(map.getCenter().lon,map.getCenter().lat)).getBounds();
  lyr.zoom = map.getZoom();

  lyr.removeFeatures(lyr.features);

  var featuresInBbox = [];
  for (var i = 0; i < lyr.unfilteredFeatures.length; i++) {
    var pt = lyr.unfilteredFeatures[i].geometry.getCentroid();
    if (lyr.bbox.contains(pt.x,pt.y)) {
      featuresInBbox.push(lyr.unfilteredFeatures[i]);
    }
  }

  var activeWeatherStations = {
     winds      : activeObs.winds && activeMode == 'observations'
    ,waves      : activeObs.waves && activeMode == 'observations'
    ,waterTemp  : activeObs.waterTemp && activeMode == 'observations'
    ,waterLevel : activeObs.waterLevel && activeMode == 'observations'
    ,all        : activeObs.all && activeMode == 'observations'
  };
  var features = [];
  lyr.possibleHits = {
     winds      : 0
    ,waves      : 0
    ,waterTemp  : 0
    ,waterLevel : 0
    ,all        : 0
  };
  for (var o in otherObs) {
    activeWeatherStations[o] = activeObs[o] && activeMode == 'observations';
    lyr.possibleHits[o]      = 0;
  }
  for (var i = 0; i < featuresInBbox.length; i++) {
    if (activeWeatherStations.winds && getWinds(featuresInBbox[i])) {
      lyr.possibleHits.winds++;
      features.push(featuresInBbox[i]);
    }
    else if (activeWeatherStations.waves && getWaves(featuresInBbox[i])) {
      lyr.possibleHits.waves++;
      features.push(featuresInBbox[i]);
    }
    else if (activeWeatherStations.waterTemp && getWaterTemp(featuresInBbox[i])) {
      lyr.possibleHits.waterTemp++;
      features.push(featuresInBbox[i]);
    }
    else if (activeWeatherStations.waterLevel && getWaterLevel(featuresInBbox[i])) {
      lyr.possibleHits.waterLevel++;
      features.push(featuresInBbox[i]);
    }
    else {
      var found = false;
      for (var o in otherObs) {
        if (!found && activeWeatherStations[o] && getOtherObs(featuresInBbox[i],otherObs[o].topObsName,otherObs[o].units)) {
          lyr.possibleHits[o]++;
          features.push(featuresInBbox[i]);
          found = true;
        }
      }
      if (!found && activeWeatherStations.all) {
        lyr.possibleHits.all++;
        features.push(featuresInBbox[i]);
      }
    }
  }

  features = cullObs(features,activeWeatherStations);

  var f;
  if (guaranteeFeature) {
    for (var i = 0; i < features.length; i++) {
      if (features[i].attributes.provider == guaranteeFeature.provider && features[i].attributes.descr == guaranteeFeature.descr) {
        f = features[i];
      }
    }

    var found = false;
    for (var i = 0; i < guaranteeFeatures.length; i++) {
      found = found || (guaranteeFeatures[i].attributes.provider == guaranteeFeature.provider && guaranteeFeatures[i].attributes.descr == guaranteeFeature.descr);
    }
    if (!found) {
      guaranteeFeatures.push(f);
    }
  }

  features = shuffle(features).slice(0,maxFeatures);

  for (var j = 0; j < guaranteeFeatures.length; j++) {
    var foundF = false;
    for (var i = 0; i < features.length; i++) {
      if (features[i].attributes.provider == guaranteeFeatures[j].attributes.provider && features[i].attributes.descr == guaranteeFeatures[j].attributes.descr) {
        foundF = true;
        break;
      }
    }
    if (!foundF) {
      features.push(guaranteeFeatures[j]);
      lyr.possibleHits.all++;
    }
  }

  lyr.addFeatures(features);
  lyr.redraw();

  if (f) {
    selectControl.unselectAll();
    highlightControl.highlight(f);
    // immediately hide the tooltip because FF is having a problem
    if (lastHighlight && lastHighlight.toolTip) {
      lastHighlight.toolTip.hide();
    }
  }

  lyr.visibleHits = {
     winds      : features.length
    ,waves      : features.length
    ,waterTemp  : features.length
    ,waterLevel : features.length
    ,all        : features.length
  };
  for (var o in otherObs) {
    lyr.visibleHits[o] = features.length;
  }

  // log for analytics
  var logged = false;
  for (var ao in activeObs) {
    if (activeObs[ao]) {
      _gaq.push(['_trackEvent','Point observations','mode',ao]);
      logged = true;
    }
  }
  if (!logged) {
    for (var oo in otherObs) {
      if (otherObs[oo]) {
        _gaq.push(['_trackEvent','Point observations','mode',oo]);
      }
    }
  }

  checkZoomAlert(lyr);
}

function addToAssetsControl(l) {
  if (!highlightControl) {
    highlightControl = new OpenLayers.Control.SelectFeature(l,{
       highlightOnly  : true
      ,hover          : true
      ,renderIntent   : 'temporary'
      ,eventListeners : {
        beforefeaturehighlighted : function(e) {
          var summary = varSummary(e.feature,1);
          var a = preparePopup(
             e.feature
            ,summary.regx
            ,summary.type
            ,false // not drawing graph from topObs
            ,false // not drawing graph from topObs
            ,false // not from search
            ,false // not points only
          );
          var el;
          var images = document.getElementById('map').getElementsByTagName('image');
          if (Ext.isIE) {
            images = document.getElementById('map').getElementsByTagName('rect');
          }
          for (var i = 0; i < images.length; i++) {
            if (images[i]._featureId == e.feature.id) {
              el = images[i];
            }
          }
          if (!el) {
            el = document.getElementById('OpenLayers.Geometry.Point_' + (Number(e.feature.id.split('_')[e.feature.id.split('_').length - 1]) - 1));
          }
          var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(e.feature.geometry.x,e.feature.geometry.y));
          if (summary.graph) {
            if (el.mouseoverToolTip) {
              el.mouseoverToolTip.destroy();
            }
            el.mouseoverToolTip = new Ext.ToolTip({
               title        : a.title.substr(0,40) + (a.title.length > 40 ? '...' : '')
              ,html         : a.summary
              ,target       : el
              ,hideDelay    : 0
              ,dismissDelay : 0
              ,showDelay    : 0
              ,baseCls      : 'custom-x-tip'
              ,width        : 280
              ,targetXY     : [pix.x,pix.y]
            });
            el.mouseoverToolTip.show();
          }
          else {
            if (el.mouseoverToolTip) {
              el.mouseoverToolTip.destroy();
            }
            el.mouseoverToolTip = new Ext.ToolTip({
               title        : a.title.substr(0,40) + (a.title.length > 40 ? '...' : '')
              ,html         : '<table class="popup"><tr><td>Click the icon to view observations.</td></tr></table>'
              ,target       : el
              ,hideDelay    : 0
              ,dismissDelay : 0
              ,showDelay    : 0
              ,baseCls      : 'custom-x-tip'
              ,width        : 280
              ,targetXY     : [pix.x,pix.y]
            });
            el.mouseoverToolTip.show();
          }
          lastHighlight = {
             toolTip : el.mouseoverToolTip
            ,feature : e.feature
          };
        }
      }
    });
    map.addControl(highlightControl);
    highlightControl.activate();
  }
  else {
    var layers = l;
    if (highlightControl.layers) {
      for (var j = 0; j < highlightControl.layers.length; j++) {
        layers.push(highlightControl.layers[j]);
      }
    }
    else {
      layers.push(highlightControl.layer);
    }
    highlightControl.setLayer(layers);
  }

  if (!selectControl) {
    selectControl = new OpenLayers.Control.SelectFeature(l,{
      eventListeners : {
        featurehighlighted : function(e) {
          var a = preparePopup(
             e.feature
            ,false // show everything
            ,false // not drawing graph from topObs
            ,false // not drawing graph from topObs
            ,false // not drawing graph from topObs
            ,false // not from search
            ,false // not points only
          );
          if (selectPopup && selectPopup.isVisible()) {
            selectPopup.hide();
          }
          var el;
          var images = document.getElementById('map').getElementsByTagName('image');
          if (Ext.isIE) {
            images = document.getElementById('map').getElementsByTagName('rect');
          }
          for (var i = 0; i < images.length; i++) {
            if (images[i]._featureId == e.feature.id) {
              el = images[i];
            }
          }
          if (!el) {
            el = document.getElementById('OpenLayers.Geometry.Point_' + (Number(e.feature.id.split('_')[e.feature.id.split('_').length - 1]) - 1));
          }
          selectPopup = new Ext.Window({
             items        : {border : false,html : a.html}
            ,title        : a.title.substr(0,40) + (a.title.length > 40 ? '...' : '')
            ,resizable    : false
            ,anchor       : 'bottom'
            ,target       : el
            ,constrainHeader : true
            ,width        : a.nCols == 1 ? 290 : 470
            ,listeners    : {
              hide : function(tt) {
                if (typeof e != 'undefined') {
                  selectControl.unselect(e.feature);
                }
                if (!tt.isDestroyed && !Ext.isIE) {
                  tt.destroy();
                }
              }
              ,show : function(tt) {
                _gaq.push(['_trackEvent','Point observations',e.feature.attributes.provider + ' - ' + e.feature.attributes.descr,'VIEW']);
              }
            }
          });
          selectPopup.show();
        }
      }
    });
    map.addControl(selectControl);
    selectControl.activate();
  }
  else {
    var layers = l;
    if (selectControl.layers) {
      for (var j = 0; j < selectControl.layers.length; j++) {
        layers.push(selectControl.layers[j]);
      }
    }
    else {
      layers.push(selectControl.layer);
    }
    selectControl.setLayer(layers);
  }
}

function preparePopup(feature,regx,type,graph,graphTitle,fromSearch,pointsOnly) {
  var sortedObs = [];
  for (var o in feature.attributes.topObs) {
    sortedObs.push(o);
  }
  sortedObs.sort(function(a,b) {
    return a.toLowerCase().localeCompare(b.toLowerCase());
  });

  var rows    = [];
  var summary = {};
  var t;
  for (var i = 0; i < sortedObs.length; i++ ) {
    var o = sortedObs[i];
    var maxT;
    // assume all at the same time t
    if (feature.attributes.topObs[o].t) {
      if (feature.attributes.topObs[o].t && (!maxT || feature.attributes.topObs[o].t > maxT)) {
        maxT = feature.attributes.topObs[o].t;
      }
      t = new Date(feature.attributes.topObs[o].t * 1000);
      var v = [];
      for (var u in feature.attributes.topObs[o].v) {
        var tsT = 'null';
        var tsV = 'null';
        if (feature.attributes.timeSeries[o]) {
          tsT = '[' + feature.attributes.timeSeries[o].t.join(',') + ']';
          tsV = '[' + feature.attributes.timeSeries[o].v[u].join(',') + ']';
        }
        var id = Ext.id();
        v.push(
          '<a href="javascript:popupGraph('
          + '\'' + id + '\''
          + ',\'' + feature.attributes.provider + '\''
          + ',\'' + feature.attributes.descr.replace(/'/g,'\\\'') + '\''
          + ',\'' + o + '\''
          + ',\'' + u + '\''
          + ',' + tsT
          + ',' + tsV
          + ',' + fromSearch
          + ',' + pointsOnly
          + ')">' + (feature.attributes.topObs[o].v[u] ? feature.attributes.topObs[o].v[u] + '&nbsp;' + u : 'view&nbsp;plot') + '<img id="' + id + '" src="img/blank.png" width=0></a>'
        );
        if (regx && regx.o.test(o) && regx.u.test(u)) {
          summary[o] = makeNiceTopObs(o,feature.attributes.topObs[o].v[u]).value + ' ' + u;
        }
      }
      rows.push(
        '<td>' + o + '</td>'
        + '<td>' + v.join('<br>') + '</td>'
      );
    }
    else {
      rows.push(
        '<td>' + o + '</td>'
        + '<td>' + '<font color=gray>no&nbsp;report</font>' + '</td>'
      );
    }
  }

  var maxRows = 8;
  var nCols   = 1;
  if (rows.length > maxRows) {
    rows = [
       '<td><table><tr>' + rows.slice(0,rows.length / 2 + 1).join('</tr><tr>') + '</tr></table></td>'
       + '<td><table><tr>' + rows.slice(rows.length / 2 + 1).join('</tr><tr>') + '</tr></table></td>'
    ];
    nCols++;
  }
  if (maxT) {
    if (new Date().getTime() - maxT * 1000 > 3600000 * 24 * 7) {
      rows.unshift('<td colspan=2 align=center>' + new Date(maxT * 1000).format("mmm d, yyyy h:MM tt (Z)") + '</td>');
    }
    else {
      rows.unshift('<td colspan=2 align=center>' + dateToFriendlyString(new Date(maxT * 1000)) + '</td>');
    }
  }
  rows.unshift('<td colspan=2 align=center><b>' + feature.attributes.provider + ' Station ' + feature.attributes.descr + '</b></td>');
  if (feature.attributes.url != '') {
    rows.push('<tr><td colspan=2 align=center><font color=gray>Click <a target=_blank href="' + feature.attributes.url + '">here</a> for station information.</font></td></tr>');
  }
  if (feature.attributes.alternateUrl != '') {
    rows.push('<tr><td colspan=2 align=center><font color=gray>Click <a target=_blank href="' + feature.attributes.alternateUrl + '">here</a> for alternate station information.</font></td></tr>');
  }

  rows.push('<tr><td align=center><img height=1 src="img/blank.png"></td></tr>');

  var graphData = [];
  if (type && graph) {
    for (var i = 0; i < feature.attributes.timeSeries[graph.o].t.length; i++) {
      graphData.push([
         new Date(feature.attributes.timeSeries[graph.o].t[i] * 1000)
        ,feature.attributes.timeSeries[graph.o].v[graph.u][i] * 1
      ]);
    }
  }

  var obs = ['<tr><td align=center><table>'];
  if (type) {
    for (var i = 0; i < type.length; i++) {
      for (k in summary) {
        if (k.indexOf(type[i]) == 0) {
          obs.push('<tr><td>' + makeNiceTopObs(k).name + '</td><td>&nbsp;</td><td>' + summary[k] + '</td></tr>');
        }
      }
    }
  }
  if (obs.length > 1) {
    obs.push('</table></td></tr>');
  }
  else {
    obs = [];
  }

  var graphId = Ext.id();
  return {
     title     : feature.attributes.provider + ' Station ' + feature.attributes.descr
    ,html      : '<table class="popup"><tr>' + rows.join('</tr><tr>') + '</tr></table>'
    ,summary   : '<table class="hilite">'
        + '<tr><td align=center><img height=2 src="img/blank.png"></td></tr>'
        + (graphTitle ? '<tr><td align=center><b>' + graphTitle + '</b></td></tr>' : '')
        + (graphData.length > 0 ? '<tr><td align=center><div style="width:250px;height:90px" id="' + graphId + '"></div></td></tr>' : '')
        + (t && obs.length > 0 ? '<tr><td align=center>' + dateToFriendlyString(t) + '</td></tr>' : '<tr><td align=center>No reported observations.</td></tr>')
        + obs.join('')
        + '<tr><td align=center><font color=gray>Click the icon for more observations.</font></td></tr>'
        + '<tr><td align=center><img height=1 src="img/blank.png"></td></tr>'
      + '</table>'
    ,graphData : graphData
    ,graphId   : graphId
    ,closable  : true
    ,t         : t
    ,nCols     : nCols
  };
}

function makeNiceTopObs(name,value) {
  var h = {
     windspeed             : {name : 'Wind speed'       ,value : Math.round(value * 1) / 1}
    ,winddirection         : {name : 'Wind direction'   ,value : degreesToCompass(value) + ' ' + Math.round(value * 1) / 1}
    ,significantwaveheight : {name : 'Wave height'      ,value : Math.round(value * 10) / 10}
    ,windwavedirection     : {name : 'Wave direction'   ,value : degreesToCompass(value) + ' ' + Math.round(value * 1) / 1}
    ,watertemperature      : {name : 'Water temperature',value : Math.round(value * 1) / 1}
    ,waterlevel            : {name : 'Water level'      ,value : Math.round(value * 10) / 10}
  };
  for (var i in otherObs) {
    h[otherObs[i].topObsName.toLowerCase()] = {
       name  : otherObs[i].niceName
      ,value : otherObs[i].niceValue(value)
    }
  }
  return h[name.toLowerCase()] ? h[name.toLowerCase()] : {name : name,value : value};
}

function degreesToCompass(d) {
  var compass = [
     'N'
    ,'NNE'
    ,'NE'
    ,'ENE'
    ,'E'
    ,'ESE'
    ,'SE'
    ,'SSE'
    ,'S'
    ,'SSW'
    ,'SW'
    ,'WSW'
    ,'W'
    ,'WNW'
    ,'NW'
    ,'NNW'
  ];
  return compass[Math.abs(Math.round((Number(d) + 22.5) / 22.5 - 0.5) % 16)];
}

function isoDateToDate(s) {
  // 2010-01-01T00:00:00Z
  s = s.replace("\n",'');
  var p = s.split('T');
  if (p.length == 2) {
    var ymd = p[0].split('-');
    var hm = p[1].split(':');
    return new Date(
       ymd[0]
      ,ymd[1] - 1
      ,ymd[2]
      ,hm[0]
      ,hm[1]
    );
  }
  else {
    return false;
  }
}

function dateToFriendlyString(e) {
  var c = "";
  var a = new Date();
  if (a.getDate() == e.getDate()) {
    strDay = "today"
  } else {
    var b = new Date(a.getTime() + 86400000);
    var d = new Date(a.getTime() - 86400000);
    if (b.getDate() == e.getDate()) {
      strDay = "tomorrow"
    } else {
      if (d.getDate() == e.getDate()) {
        strDay = "yesterday"
      } else {
        aryDays = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
        strDay = aryDays[e.getDay()]
      }
    }
  }
  c += (e.getHours() > 12 ? e.getHours() - 12 : (e.getHours() == 0 ? 12 : e.getHours()));
  c += ":" + (e.getMinutes() < 10 ? "0" : "") + e.getMinutes() + (e.getHours() > 11 ? " pm" : " am");
  c += " " + strDay;
  c += ' (' + e.format('Z') + ')';

  // show a real date if over a week old
  if (e < new Date(a.getTime() - 1000 * 3600 * 24 * 7)) {
    return month[e.getMonth()] + ' ' + e.getDate();
  }
  else {
    return c;
  }
}

function selectWeatherStationType(o,guaranteeFeature) {
  if (new RegExp(/^(all|other)$/).test(o) || otherObs[o]) {
    hideObsLegend();
  }
  else {
    showObsLegend(o);
  }

  for (var i in activeObs) {
    activeObs[i] = i == o;
  }

  var lyr = map.getLayersByName('icon')[0];
  if (lyr && lyr.unfilteredFeatures.length > 0) {
    lyr.bbox = false;
    if (guaranteeFeature) {
      lyr.listenForMoveEnd = false;
      zoomToStation(guaranteeFeature.provider,guaranteeFeature.descr);
    }
    Ext.defer(function(){syncIconLayerWithData(lyr,guaranteeFeature)},10);
  }

  var a = ['Winds','Waves','WaterTemp','WaterLevel','Other'];
  for (var i = 0; i < a.length; i++) {
    var but = Ext.getCmp('weatherStationsOptions' + a[i] + 'Button');
    var lbl = document.getElementById('weatherStationsOptions' + a[i] + 'Label');
    if (lbl) {
      lbl.className = 'belowButtonsText' + (but.pressed ? 'Black' : 'Gray');
    }
  }
}

function iconContext(scaleFactor,ctl) {
  return {
    getExternalGraphic : function(f) {
      return varSummary(f,scaleFactor,ctl).url;
    }
    ,getGraphicWidth    : function(f) {
      return varSummary(f,scaleFactor,ctl).w;
    }
    ,getGraphicHeight   : function(f) {
      return varSummary(f,scaleFactor,ctl).h;
    }
    ,getPointRadius     : function(f) {
      return varSummary(f,scaleFactor,ctl).w / 2;
    }
    ,getDisplay         : function(f) {
      var s = varSummary(f,scaleFactor,ctl);
      return s.w == 0 && s.h == 0 ? 'none' : 'visible';
    }
  };
}

function varSummary(f,scaleFactor,ctl) {
  var hilite  = '';
  var imgSize = [74,75];
  if (typeof ctl == 'string' && ctl != 'default') {
    hilite  = '&shadow=' + ctl;
  } 
  if (activeObs.winds && activeMode == 'observations') {
    var o = getWinds(f);
    if (o) {
      var url = 'icon.php?size=115,115&cpt=' + obsCptRanges['winds'] + '&mag=' + Math.round(o.spd) + '&dir=' + Math.round(o.dir) + '&barb&noCircle' + hilite;
      var w   = imgSize[0] * (map.getZoom() < minZoom.winds ? scaleFactor : 1);
      var h   = imgSize[1] * (map.getZoom() < minZoom.winds ? scaleFactor : 1);
      if (!o.spd) {
        url = 'img/noData' + hilite + '.png';
        w   = hilite != '' ? 40 : 20;
        h   = hilite != '' ? 40 : 20;
      }
      return {
         url   : url
        ,w     : w
        ,h     : h
        ,type  : ['WindSpeed','WindDirection']
        ,graph : {
           o : 'WindSpeed'
          ,u : 'knots'
        }
        ,regx  : {
           o : /^(WindSpeed|WindDirection)$/
          ,u : /^(knots|deg)$/
        }
        ,leg    : 'winds'
        ,topObs : 'Winds'
      }
    }
  }
  else if (activeObs.waves && activeMode == 'observations') {
    var o = getWaves(f);
    if (o) {
      var url = 'icon.php?size=115,115&cpt=' + obsCptRanges['waves'] + '&mag=' + (Math.round(o.spd * 10) / 10) + '&dir=' + Math.round((Number(o.dir) - 180 < 0) ? Number(o.dir) + 180 : Number(o.dir) - 180) + '&arrow' + hilite;
      var w   = imgSize[0] * (map.getZoom() < minZoom.waves ? scaleFactor : 1);
      var h   = imgSize[1] * (map.getZoom() < minZoom.waves ? scaleFactor : 1);
      if (!o.spd) {
        url = 'img/noData' + hilite + '.png';
        w   = hilite != '' ? 40 : 20;
        h   = hilite != '' ? 40 : 20;
      }
      return {
         url   : url
        ,w     : w
        ,h     : h
        ,type  : ['SignificantWaveHeight','WindWaveDirection']
        ,graph : {
           o : 'SignificantWaveHeight'
          ,u : 'ft'
        }
        ,regx  : {
           o : /^(SignificantWaveHeight|WindWaveDirection)$/
          ,u : /^(ft|deg)$/ 
        }
        ,leg    : 'waves'
        ,topObs : 'Waves'
      }
    }
  }
  else if (activeObs.waterTemp && activeMode == 'observations') {
    var o = getWaterTemp(f);
    if (o) {
      var url = 'icon.php?size=115,115&cpt=' + obsCptRanges['watertemp'] + '&mag=' + Math.round(o.mag) + hilite;
      var w   = imgSize[0] * (map.getZoom() < minZoom.waterTemp ? scaleFactor : 1);
      var h   = imgSize[1] * (map.getZoom() < minZoom.waterTemp ? scaleFactor : 1);
      if (!o.mag) {
        url = 'img/noData' + hilite + '.png';
        w   = hilite != '' ? 40 : 20;
        h   = hilite != '' ? 40 : 20;
      }
      return {
         url   : url
        ,w     : w
        ,h     : h
        ,type  : ['WaterTemperature']
        ,graph : {
           o : 'WaterTemperature'
          ,u : 'F'
        }
        ,regx  : {
           o : /^WaterTemperature/
          ,u : /^F$/
        }
        ,leg    : 'watertemp'
        ,topObs : 'WaterTemp'
      }
    }
  }
  else if (activeObs.waterLevel && activeMode == 'observations') {
    var o = getWaterLevel(f);
    if (o) {
      var url = 'icon.php?size=115,115&cpt=' + obsCptRanges['waterlevel'] + '&mag=' + (Math.round(o.mag * 10) / 10) + hilite;
      var w   = imgSize[0] * (map.getZoom() < minZoom.waterLevel ? scaleFactor : 1);
      var h   = imgSize[1] * (map.getZoom() < minZoom.waterLevel ? scaleFactor : 1);
      if (!o.mag) {
        url = 'img/noData' + hilite + '.png';
        w   = hilite != '' ? 40 : 20;
        h   = hilite != '' ? 40 : 20;
      }
      return {
         url   : url
        ,w     : w
        ,h     : h
        ,type  : ['WaterLevel']
        ,graph : {
           o : 'WaterLevel'
          ,u : 'ft'
        }
        ,regx  : {
           o : /^WaterLevel$/
          ,u : /^ft$/
        }
        ,leg    : 'waterlevel'
        ,topObs : 'WaterLevel'
      }
    }
  }
  else {
    for (var o in otherObs) {
      if (activeObs[o] && activeMode == 'observations') {
        var oo = getOtherObs(f,otherObs[o].topObsName,otherObs[o].units);
        if (oo) {
          return {
             url   : 'img/site' + hilite + '.png'
            ,w     : hilite != '' ? 40 : 20
            ,h     : hilite != '' ? 40 : 20
            ,type  : [otherObs[o].topObsName]
            ,graph : {
               o : otherObs[o].topObsName
              ,u : otherObs[o].units
            }
            ,regx  : {
               o : new RegExp(otherObs[o].topObsName)
              ,u : new RegExp(otherObs[o].units)
            }
            ,leg    : o
            ,topObs : otherObs[o].topObsName
          }
        }
      }
    }
  }
  if (activeObs.all && activeMode == 'observations') {
    return {
       url   : 'img/site' + hilite + '.png'
      ,w     : hilite != '' ? 40 : 20
      ,h     : hilite != '' ? 40 : 20
      ,type  : ['All']
      ,graph : null
      ,regx  : null
      ,leg    : 'All'
      ,topObs : 'All'
    };
  }
  return {
     url  : 'img/blank.png'
    ,w    : 0
    ,h    : 0
  };
}

function popupGraph(id,provider,descr,varName,varUnits,t,v,fromSearch,pointsOnly) {

  function drawPlotCallback(r) {
    var json = new OpenLayers.Format.JSON().read(r.responseText);
    if (json) {
      goGraph(json.id,json.provider,json.descr,json.varName,json.varUnits,null,null,json.f,json.w,json.h,pointsOnly);
    }
    else {
      if (selectPopup && !selectPopup.isDestroyed) {
        selectPopup.getEl().unmask();
        if (selectPopup.items && selectPopup.items.items[0]) {
          selectPopup.items.items[0].getEl().unmask();
        }
      }
      Ext.Msg.alert('Error',"We're sorry, but there was an error making this plot.");
    }
  }

  function getSpecificObsCallback(r) {
    var json = new OpenLayers.Format.JSON().read(r.responseText);
    if (!json) {
      if (selectPopup && !selectPopup.isDestroyed) {
        selectPopup.getEl().unmask();
        if (selectPopup.items && selectPopup.items.items[0]) {
          selectPopup.items.items[0].getEl().unmask();
        }
      }
      Ext.Msg.alert('Error',"We're sorry, but there was an error making this plot.");
    }
    else {
      if (json.v && json.v.z) {
        OpenLayers.Request.issue({
           url      : '/cgi-bin/drawPlot'
          ,method   : 'POST'
          ,data     : r.responseText
          ,callback : OpenLayers.Function.bind(drawPlotCallback,null)
        });
      }
      else if (json.v) {
        goGraph(json.id,json.provider,json.descr,json.varName,json.varUnits,json.t,json.v,null,500,200,pointsOnly);
      }
      else {
        if (selectPopup && !selectPopup.isDestroyed) {
          selectPopup.getEl().unmask();
          if (selectPopup.items && selectPopup.items.items[0]) {
            selectPopup.items.items[0].getEl().unmask();
          }
        }
        Ext.Msg.alert('Error retrieving data',"We're sorry, but there was an error retrieving the data.");
      }
    }
  }

  if (selectPopup && !selectPopup.isDestroyed) {
    selectPopup.items.items[0].getEl().mask('<table class="maskText"><tr><td>Loading...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>');
  }

  if (fromSearch) {
    OpenLayers.Request.issue({
       url      : 'getSpecificObs.php'
         + '?id='       + id
         + '&provider=' + provider
         + '&descr='    + descr
         + '&varName='  + varName
         + '&varUnits=' + varUnits
         + '&fromSearch=true'
      ,method   : 'POST'
      ,headers  : {'Content-Type' : 'application/x-www-form-urlencoded'}
      ,data     : selectPopup.fromSearch
      ,callback : OpenLayers.Function.bind(getSpecificObsCallback,null)
    });
  }
  else {
    OpenLayers.Request.issue({
       url      : 'getSpecificObs.php'
         + '?id='       + id 
         + '&provider=' + provider 
         + '&descr='    + descr 
         + '&varName='  + varName 
         + '&varUnits=' + varUnits
         + '&fromSearch=false'
      ,callback : OpenLayers.Function.bind(getSpecificObsCallback,null)
    })
    _gaq.push(['_trackEvent','Point observations',provider + ' - ' + descr,varName]);
  }
}

function goGraph(id,provider,descr,varName,varUnits,t,v,img,w,h,pointsOnly) {
  if (selectPopup && !selectPopup.isDestroyed) {
    selectPopup.items.items[0].getEl().unmask();
  }

  var pos     = getOffset(document.getElementById(id));
  var graphId = Ext.id();

  var tz   = '';
  if (t.length > 0) {
    tz = ' Times are in ' + new Date(t[0] * 1000).format('Z') + '.';
  }
  var html = '<tr><td align=center><div style="width:' + w + 'px;height:' + h + 'px" id="' + graphId + '"></div></td></tr>'
    + '<tr><td align=center><font color=gray>Mouseover the line to view details.' + tz + '</font></td></tr>';
  if (img) {
    html = '<tr><td align=center><img src="' + img + '"></td></tr>';
  }

  var graphData = [];
  for (var i = 0; i < t.length; i++) {
    graphData.push([new Date(t[i] * 1000),Number(v[i])]);
  }

  var timeAxisFormat = "%a<br>%l:%M %p";
  var largeTimeSpan  = false;
  if (t.length >= 2 && t[t.length - 1] - t[0] > 3600 * 24 * 7) {
    timeAxisFormat = "%b %d<br>%Y";
    largeTimeSpan  = true;
  }

  var sto = new Ext.data.ArrayStore({
     fields : ['time','value']
    ,data   : graphData
  });

  var win = new Ext.Window({
     x               : pos.left - w * 1.1
    ,y               : pos.top + 15
    ,autoHeight      : true
    ,width           : w + 30
    ,title           : provider + ' Station ' + descr
    ,constrainHeader : true
    ,resizable       : false
    ,layout          : 'fit'
    ,closeAction     : 'hide'
    ,items           : new Ext.TabPanel({
       activeTab      : 0
      ,deferredRender : false
      ,width          : Number(w) + 10
      ,border         : false
      ,resizeTabs     : true
      ,tabWidth       : 200
      ,plain          : true
      ,items          : [
        {
           title     : 'View as chart'
          ,iconCls   : 'chartIcon'
          ,tabTip    : 'View data in a chart format'
          ,html      : '<table style="width:100%"><tr><td align=center><table class="popup">'
            + '<tr><td align=center><img height=5 src="img/blank.png"></td></tr>'
            + '<tr><td align=center><b>' + varName + ' (' + varUnits + ')' + '</b></td></tr>'
            + html
            + '<tr><td align=center><img height=4 src="img/blank.png"></td></tr>'
            + '</table></td></tr></table>'
          ,border    : false
          ,listeners : {afterrender : function() {
            if (img) {
              return;
            }
            var prevPt;
            $('#' + graphId).bind('plothover',function(event,pos,item) {
              if (item) {
                var x = new Date(item.datapoint[0]);
                var y = item.datapoint[1];
                if (prevPoint != item.dataIndex) {
                  $('#tooltip').remove();
                  showToolTip(item.pageX,item.pageY,y + ' ' + item.series.label + '<br/>' + (!largeTimeSpan ? dateToFriendlyString(x) : new Date(x).format("mmm d, yyyy'<br>'h:MM tt (Z)")));
                }
                prevPoint = item.dataIndex;
              }
              else {
                $('#tooltip').remove();
                prevPoint = null;
              }
            });

            var p = $.plot(
               $('#' + graphId)
              ,[{label : varUnits,data : graphData,color : '#99BBE8',points : {show : pointsOnly},lines : {show : !pointsOnly}}]
              ,{
                 xaxis     : {mode  : "time",timezone : 'browser',twelveHourClock : true,timeformat : timeAxisFormat}
                ,crosshair : {mode  : 'x'   }
                ,grid      : {backgroundColor : {colors : ['#fff','#eee']},borderWidth : 1,borderColor : '#99BBE8',hoverable : true}
                ,zoom      : {interactive : true}
                ,pan       : {interactive : true}
                ,legend    : {show : false}
              }
            );
          }}
        }
        ,new Ext.grid.GridPanel({
           title            : 'View as data table'
          ,iconCls          : 'tableIcon'
          ,tabTip           : 'View data in a table format'
          ,store            : sto
          ,enableHdMenu     : false
          ,disableSelection : true
          ,forceFit         : true
          ,cls              : 'gridBorderTop'
          ,columns          : [
             {header : 'Time',dataIndex : 'time',id : 'time',width : 200,type : 'date',sortable : true,renderer : function(val) {return !largeTimeSpan ? dateToFriendlyString(val) : new Date(val).format("mmm d, yyyy h:MM tt (Z)")}}
            ,{header : varName + ' (' + varUnits + ')',dataIndex : 'value',id : 'value'}
          ]
          ,autoExpandColumn : 'value'
        })
        ,{
           title     : 'Export data'
          ,iconCls   : 'exportIcon'
          ,tabTip    : 'Export data and save to disk'
          ,shown     : false
          ,html      : '<table style="width:100%"><tr><td align=center><table class="popup"><tr><td align=center><br>Please wait while the data is prepared for export.</td></tr></table></td></tr></table>'
          ,listeners : {show : function(p) {
            if (!p.shown) {
              OpenLayers.Request.issue({
                 url      : 'export.php'
                ,method   : 'POST'
                ,headers  : {'Content-Type' : 'application/x-www-form-urlencoded'}
                ,data     : OpenLayers.Util.getParameterString({
                   data  : Ext.encode(graphData)
                  ,'var' : Ext.encode(varName)
                  ,uom   : Ext.encode(varUnits)
                  ,site  : Ext.encode(provider + ' Station ' + descr)
                  ,tz    : Ext.encode(new Date(graphData[0][0] * 1000).format('Z'))
                  ,sess  : sessionId
                  ,id    : Ext.id()
                })
                ,callback : function(r) {
                  var json = new OpenLayers.Format.JSON().read(r.responseText);
                  var html = "<br>We\'re sorry, but there was a problem creating a file for export.";
                  if (json) {
                    html = '<br>Click the icon below to download your file.<br><br><br><a href="csv.php?dir=' + json.dir + '&csv=' + json.csv + '&site=' + json.site + '&var=' + json['var'] + '" target=_blank><img title="Download" src="img/download64.png"></a>';
                  }
                  p.update('<table style="width:100%"><tr><td align=center><table class="popup"><tr><td align=center>' + html + '</td></tr></table></td></tr></table>');
                }
              });
            }
          }}
        }
      ]
    })
  });
  win.show();
  win.setZIndex(1000000);
}

function getOffset( el ) {
    var _x = 0;
    var _y = 0;
    while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
        _x += el.offsetLeft - el.scrollLeft;
        _y += el.offsetTop - el.scrollTop;
        el = el.offsetParent;
    }
    return { top: _y, left: _x };
}

function zoomToStation(provider,descr) {
  for (var i = 0; i < guaranteeFeatures.length; i++) {
    highlightControl.unhighlight(guaranteeFeatures[i]);
  }
  var lyr = map.getLayersByName('icon')[0];
  var f;
  for (var i = 0; i < lyr.unfilteredFeatures.length; i++) {
    if (lyr.unfilteredFeatures[i].attributes.provider == provider && lyr.unfilteredFeatures[i].attributes.descr == descr) {
      f = lyr.unfilteredFeatures[i];
    }
  }
  if (f) {
    var lonLat = new OpenLayers.LonLat(f.geometry.x,f.geometry.y);
    map.setCenter(lonLat,9);
  }
}

function showToolTip(x,y,contents) {
  $('<div id="tooltip">' + contents + '</div>').css({
     position           : 'absolute'
    ,display            : 'none'
    ,top                : y + 10
    ,left               : x + 10
    ,border             : '1px solid #99BBE8'
    ,padding            : '2px'
    ,'background-color' : '#fff'
    ,opacity            : 0.80
    ,'z-index'          : 10000001
  }).appendTo("body").fadeIn(200);
}

function getWinds(f) {
  if (!f.attributes.topObs['WindSpeed'] && !f.attributes.topObs['WindDirection']) {
    return false;
  }
  return {
     spd : (f.attributes.topObs['WindSpeed'] ? f.attributes.topObs['WindSpeed'].v['knots'] : null)
    ,dir : (f.attributes.topObs['WindDirection'] ? f.attributes.topObs['WindDirection'].v['deg'] : null)
  };
}

function getWaves(f) {
  if (!f.attributes.topObs['SignificantWaveHeight']) {
    return false;
  }
  return {
     spd : f.attributes.topObs['SignificantWaveHeight'].v['ft']
    ,dir : f.attributes.topObs['WindWaveDirection'] ? f.attributes.topObs['WindWaveDirection'].v['deg'] : -9999
  };
}

function getWaterTemp(f) {
  if (f.attributes.topObs['WaterTemperature']) {
    return {
      mag : f.attributes.topObs['WaterTemperature'].v['F']
    };
  }
  for (var s in f.attributes.topObs) {
    if (/WaterTemperature/.test(s)) {
      return {
        mag : 'all'
      };
    }
  }
  return false;
}

function getWaterLevel(f) {
  if (!f.attributes.topObs['WaterLevel']) {
    return false;
  }
  return {
    mag : f.attributes.topObs['WaterLevel'].v['ft']
  };
}

function getMyObs(f) {
  if (!f.attributes.topObs['MyObs']) {
    return false;
  }
  return true;
}

function getOtherObs(f,name,units) {
  if (!f.attributes.topObs[name]) {
    return false;
  }
  return {
    mag : f.attributes.topObs[name].v[units]
  };
}

function getAllObs() {
  return true;
}

function checkZoomAlert(lyr) {
  var possible = lyr.possibleHits;
  var visible = lyr.visibleHits;
  var c = {};
  if (activeObs.winds && activeMode == 'observations') {
    c = {
       o : 'wind'
      ,p : possible.winds
      ,v : visible.winds  
    };
  }
  else if (activeObs.waves && activeMode == 'observations') {
    c = {
       o : 'wave'
      ,p : possible.waves
      ,v : visible.waves
    };
  }
  else if (activeObs.waterTemp && activeMode == 'observations') {
    c = {
       o : 'water temperature'
      ,p : possible.waterTemp
      ,v : visible.waterTemp
    };
  }
  else if (activeObs.waterLevel && activeMode == 'observations') {
    c = {
       o : 'water level'
      ,p : possible.waterLevel
      ,v : visible.waterLevel
    };
  }
  else {
    var found = false;
    for (var o in otherObs) {
      if (!found && activeObs[o] && activeMode == 'observations') {
        c = {
           o : makeNiceTopObs(otherObs[o].topObsName).name.toLowerCase()
          ,p : possible[o]
          ,v : visible[o]
        }
        found = true;
      }
    }
    if (!found && activeObs.all && activeMode == 'observations') {
      c = {
         o : ''
        ,p : possible.all
        ,v : visible.all
      };
    }
  }

  if (viewer == 'lite') {
    document.getElementById('mapMessagesHtml').innerHTML = 'To view observations, left-click a marker on the map.';
  }

  if (c.p == 0) {
    document.getElementById('mapMessagesHtml').innerHTML = 'There are no available ' + c.o  + ' stations in this area.';
  }
  else {
    if (viewer == 'lite') {
      document.getElementById('mapMessagesHtml').innerHTML += c.v != c.p ? ' <font style="color:#15428B"><b>Zoom in to view more stations.</b></font>' : '';
    }
    else {
      document.getElementById('mapMessagesHtml').innerHTML = 'You are currently viewing approximately ' + c.v + ' of ' + c.p + ' ' + c.o  + ' stations.' + (c.v != c.p ? ' Zoom in to view more.' : '');
    }
  }
  document.getElementById('mapMessagesButtonGroup').style.visibility = (c.v != c.p || c.p == 0 || (activeMode == 'observations' && viewer == 'lite')) && lyr.visibility ? 'visible' : 'hidden';
  if (viewer != 'lite') {
    zoomAlert.hits = 0;
  }
}

function mapClick(xy,allMaps) {
  forecastLoadstartMask();
  var l = map.getLayersByName('queryPt')[0];
  l.removeFeatures(l.features);
  var lonLat = map.getLonLatFromPixel(xy);
  var f      = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(lonLat.lon,lonLat.lat));
  lonLat.transform(proj900913,proj4326);
  Ext.getCmp('forecastWindow').setTitle('Forecast data (' + convertDMS(lonLat.lat.toFixed(5), "LAT") + ' ' + convertDMS(lonLat.lon.toFixed(5), "LON") + ')');
  l.addFeatures(f);
  var maps = [];
  if (!Ext.getCmp('forecastWindow').hidden) {
    maps = [Ext.getCmp('forecastMapsTypeComboBox').getValue()];
  }
  if (allMaps) {
    Ext.getCmp('forecastMapsTypeComboBox').getStore().each(function(rec) {
      if (rec.get('conditionsReport')) {
        maps.push(rec.get('id'));
      }
    });
    maps.push(Ext.getCmp('byCatchMapsTypeComboBox').getValue());
    Ext.getCmp('weatherMapsTypeComboBox').getStore().each(function(rec) {
      if (rec.get('conditionsReport')) {
        maps.push(rec.get('id'));
      }
    });
  }
  for (var i = 0; i < maps.length; i++) {
    maps[i] = maps[i].replace(/&/g,'%26');
  }
  var url = 'getPointForecast.php'
    + '?d='       + new Date().getTime()
    + '&x='       + xy.x
    + '&y='       + xy.y
    + '&w='       + map.size.w
    + '&h='       + map.size.h
    + '&bbox='    + map.getExtent().toBBOX()
    + '&srs='     + proj3857
    + '&lon='     + lonLat.lon
    + '&lat='     + lonLat.lat
    + '&pLonLat=' + encodeURIComponent(convertDMS(lonLat.lat.toFixed(5), "LAT") + ' ' + convertDMS(lonLat.lon.toFixed(5), "LON"))
    + '&allMaps=' + allMaps
    + '&maps='    + Ext.encode(maps)
    + '&dt='      + (allMaps ? 5 : 0)
  forecastUrls[url] = true;

  if (viewer == 'lite') {
    Ext.MessageBox.show({
       title        : 'Please wait'
      ,msg          : 'Creating condition report...'
      ,width        : 300
      ,wait         : true
      ,waitConfig   : {interval : 200}
    });
  }

  OpenLayers.Request.issue({
     url      : url
    ,callback : OpenLayers.Function.bind(processForecastCallback,null,url)
  });

  function processForecastCallback(url,r) {
    var json = new OpenLayers.Format.JSON().read(r.responseText);
    if (json.allMaps) {
      var data = {};
      for (var v in json.data) {
        if (!new RegExp(/^(Bycatch|Current|Wave|Wind|Water|surface water temperature|bottom water temperature|Salinity|Chlorophyll)/i).test(v)) {
          continue;
        }
        data[v] = json.data[v];
      }
      printMap(data,json.pLonLat);
    }
    else {
      populateForecastPanel('forecastPanel',new RegExp(/^(Ice|Wave|Current|Wind|Water|surface water temperature|bottom water temperature|Salinity)/i),json);
      // populateForecastPanel('forecastTabPanelWeather',new RegExp(/^NWS.*(Conditions|Temperature|Forecast)/i),json);
      if (Ext.getCmp('wwaRadioGroupYes').getValue()) {
        populateWWAPanel('wwaActivityPanel',new RegExp(/^NWS Watches, warnings, and advisories/),json);
      }
    }
    delete forecastUrls[url];
    var hits = 0;
    for (var i in forecastUrls) {
      hits++;
    }
    if (hits == 0) {
      forecastLoadendUnmask();
    }
  }

  function getVal(vals,idx,nextIdx) {
    for (var i in vals) {
      if (idx <= i && i <= nextIdx) {
        return {val : vals[i],idx : i};
      }
    }
    return false;
  }

  function populateWWAPanel(panelId,varRegExp,json) {
    var html = [];
    for (var v in json.data) {
      if (!varRegExp.test(v)) {
        continue;
      }
      for (var i = 0; i < json.data[v].v.length; i++) {
        html.push('&nbsp;&nbsp;<a target=_blank href="' + json.data[v].v[i].hazardTextURL + '">' + json.data[v].v[i].headline + '</a>');
      }
    }
    var panel = Ext.getCmp(panelId);
    panel.removeAll();
    if (html.length > 0) {
      panel.update(html.join('<br><img height=5 src="img/blank.png"><br>'));
    }
    else {
      panel.update('');
    }
    panel.doLayout();
  }

  function populateForecastPanel(panelId,varRegExp,json) {
    var tHits = {};
    for (var v in json.data) {
      if (!varRegExp.test(v)) {
        continue;
      }
      for (var t in json.data[v].v) {
        if (t != 'remove') {
          tHits[t] = true;
        }
      }
    }
    var times = [];
    for (var t in tHits) {
      times.push(t);
    } 
    times.sort(function(a,b){return a - b});
    var dMin = new Date(times[0] * 1000);
    var dMax = new Date(times[times.length - 1] * 1000);
    var dIncr = 24 * 3600 * 1000;
    for (var i = 1; i < times.length; i++) {
      var dt = (times[i] - times[i - 1]) * 1000;
      if (0 < dt && dt < dIncr) {
        dIncr = dt;
      }
    }

    var hours         = [];
    var data          = [];
    var struct        = {};
    var notOnlyZoneFC = false;
    for (var v in json.data) {
      if (!varRegExp.test(v)) {
        continue;
      }
      notOnlyZoneFC = notOnlyZoneFC || !(new RegExp(/NWS Forecast : (coastal|offshore)/).test(v));
      var row = [{html : v.replace(/^NWS /,'').replace(/ /g,'&nbsp;'),cellCls : 'forecastHeader'}];
      for (var i = dMin.getTime(); i <= dMax.getTime(); i += dIncr) {
        var d = new Date(i); 
        var val = false;
        var h = getVal(json.data[v].v,d.getTime() / 1000,new Date(d.getTime() + dIncr).getTime() / 1000);
        if (h) {
          delete json.data[v].v[h.idx];
          val = h.val;
        }
        if (typeof val == 'string') {
          if (new RegExp(/\.(jpg|png)$/).test(val)) {
            var img = ["<img src='","'>"];
            row.push({html : img[0] + val + img[1],cellCls : 'forecastData',colspan : 1});
          }
          else {
            var img = ['',''];
            var cls = 'forecastData';
            if (new RegExp(/direction/).test(v)) {
              img = ["<img width=25 height=25 src='http://charthorizon.com/map-htdocs/icons/arrows/0_0_0.",".png'>"];
            }
            else if (new RegExp(/Text Forecast|Forecast : /).test(v)) {
              cls = 'hazardData';
            }
            row.push({html : img[0] + val + img[1],cellCls : cls,colspan : 1});
          }
        }
        else {
          if (row.length == 1) {
            row.push({html : '&nbsp;',cellCls : 'forecastData',colspan : 1});
          }
          else {
            row[row.length - 1].colspan++;
          }
        }
        if (data.length == 0) {
          // add a space to treat these as chars (the 0th day is not always first!)
          var day  = ' ' + d.getDay();
          var amPm = d.getHours() < 12 ? 'AM' : 'PM';
          var h    = d.getHours() % 12;
          h == 0 ? h = 12 : null;
          hours.push({html : String(h),cellCls : 'forecastHeader'});
          if (!struct[day]) {
            struct[day] = {};
          }
          if (!struct[day][amPm]) {
            struct[day][amPm] = 0;
          }
          struct[day][amPm]++;
        }
      }
      data.push(row);
    }
    var days      = [];
    var meridians = [];
    for (var d in struct) {
      var hits = 0;
      for (var m in struct[d]) {
        meridians.push({
           html    : m
          ,colspan : struct[d][m]
          ,cellCls : 'forecastHeader'
        });
        hits += struct[d][m];
      }
      days.push({
         html    : weekday[d.replace(' ','')] + ' (' + new Date().format('Z') + ')'
        ,colspan : hits
        ,cellCls : 'forecastHeader'
      });
    }

    var byCatchReportButton = {html : '&nbsp;',rowspan : 3};
    if (Ext.getCmp('byCatchTabPanel')) {
      byCatchReportButton = {
         xtype   : 'container'
        ,autoEl  : {tag : 'center'}
        ,rowspan : 3
        ,border  : false
        ,items   : new Ext.Button({
           text      : '<table><tr><td style="text-align:center">Create&nbsp;a<br>conditions&nbsp;report</td></tr></table>'
          ,id        : 'byCatchReportButton'
          ,icon      : 'img/fishcatch32.png'
          ,tooltip   : 'Create a conditions report'
          ,scale     : 'large'
          ,iconAlign : 'top'
          ,cls       : 'forecastTableNoStyle'
          ,handler   : function() {
            var lyr = map.getLayersByName('queryPt')[0];
            if (Ext.getCmp('byCatchTabPanel').getActiveTab().id != 'showByCatchPanel' || lyr.features.length <= 0) {
              Ext.Msg.alert('Bycatch report error','Please click the Show bycatch data button and click in a bycatch grid before attempting to create a report.');
              return;
            }
            var f = lyr.features[0].clone();
            var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(f.geometry.x,f.geometry.y));
            mapClick({x : pix.x,y : pix.y},true);
          }
        })
      };
    }

    var panel = Ext.getCmp(panelId);
    panel.removeAll();
    if (hours.length > 0) {
      panel.add(
        new Ext.Panel({
           border       : false
          ,layout       : 'table'
          ,cls          : 'forecastTable'
          ,layoutConfig : {
            columns : hours.length + 1
          }
          ,defaults     : {
            border : false
          }
          ,items        : notOnlyZoneFC ? [[
             byCatchReportButton
            ,days
            ,meridians
            ,hours
            ,data].concat(forecastFooter ?
              [{},{
                 colspan : hours.length
                ,cellCls : 'forecastFooter'
                ,html    : forecastFooter
              }] : []
            )
          ] : [data]
        })
      );
    }
    else {
      panel.add({html : '<img src="img/blank.png" height=4><br>No forecast data available for this point. Click anywhere on the map to see a forecast.',bodyStyle : 'padding:6px',border : false,cls : 'forecastData'});
    }
    panel.enable();
    panel.doLayout();
  }
}

function forecastModeChanged() {
  var l = map.getLayersByName('queryPt')[0];
  l.removeFeatures(l.features);
  if (selectPopup && !selectPopup.isDestroyed) {
    selectPopup.hide();
  }
}

function forecastLoadstartMask() {
  if (Ext.getCmp('forecastWindow').getEl()) {
    Ext.getCmp('forecastWindow').getEl().mask('<table><tr><td>Retrieving conditions...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');
  }
  if (activeMode == 'weather' && Ext.getCmp('wwaRadioGroupYes').getValue()) {
    mapLoadstartMask('weatherHazards','wwa');
  }
}

function forecastLoadendUnmask() {
  if (Ext.getCmp('forecastWindow').getEl()) {
    Ext.getCmp('forecastWindow').getEl().unmask();
  }
  if (Ext.getCmp('wwaRadioGroupYes').getValue()) {
    mapLoadendUnmask('weatherHazards','wwa');
  }
}

function inArray(arr,obj) {
  for(var i = 0; i < arr.length; i++) {
    if (arr[i] == obj) return true;
  }
}

function clearForecast() {
  var cmp = Ext.getCmp('forecastPanel');
  cmp.removeAll();
  cmp.add({html : '<img src="img/blank.png" height=4><br>Click anywhere on the map to see a forecast.',bodyStyle : 'padding:6px',border : false,cls : 'forecastData'});
  cmp.doLayout();
  var el = Ext.getCmp('wwaActivityPanel');
  el.update('<table width="100%"><tr><td align=center><img src="img/wwa.png"></td></tr></table>');
  el.doLayout();
  var l = map.getLayersByName('queryPt')[0];
  l.removeFeatures(l.features);
}

function addWunderground(rec) {
  var lyr = new OpenLayers.Layer.WMS(
     rec.get('id')
    ,rec.get('getMapUrl')
    ,{
    }
    ,{
       isBaseLayer      : false
      ,projection       : proj3857
      ,singleTile       : rec.get('singleTile')
      ,wrapDateLine     : true
      ,visibility       : rec.get('visibility')
      ,opacity          : rec.get('opacity')
      ,noMagic          : true
      ,transitionEffect : 'resize'
      ,panel            : rec.get('panel')
      ,moreInfo         : rec.get('moreInfo')
      ,bbox             : rec.get('bbox')
      ,legend           : rec.get('legend')
      ,attribution      : rec.get('id').replace('Weather Underground','') + ' image courtesy of <a target=_blank href="http://wunderground.com">Weather Underground</a>.'
      ,getURL           : function(bounds) {
        bounds = this.adjustBounds(bounds);
        bounds.transform(proj900913,proj4326);
        var bbox = bounds.toArray();
        var imageSize = this.getImageSize();
        return OpenLayers.Util.urlAppend(
           lyr.url
          ,OpenLayers.Util.getParameterString({
             minlon            : bbox[0]
            ,minlat            : bbox[1]
            ,maxlon            : bbox[2]
            ,maxlat            : bbox[3]
            ,width             : imageSize.w
            ,height            : imageSize.h
            ,'reproj.automerc' : 1
          })
        );
      }
    }
  );

  return lyr;
}

function addWMS(rec) {
  var lyr = new OpenLayers.Layer.WMS(
     rec.get('id')
    ,rec.get('getMapUrl')
    ,{
       layers      : rec.get('getMapLayers')
      ,transparent : true
      ,styles      : rec.get('styles')
      ,format      : rec.get('format')
    }
    ,{
       isBaseLayer      : false
      ,projection       : proj3857
      ,singleTile       : rec.get('singleTile')
      ,wrapDateLine     : true
      ,visibility       : rec.get('visibility')
      ,opacity          : rec.get('opacity')
      ,noMagic          : true
      ,transitionEffect : 'resize'
      ,panel            : rec.get('panel')
      ,moreInfo         : rec.get('moreInfo')
      ,bbox             : rec.get('bbox')
      ,legend           : rec.get('legend')
    }
  );
  return lyr;
}

function addTMS(rec) {
  return new OpenLayers.Layer.TMS(
     rec.get('id')
    ,rec.get('getMapUrl')
    ,{
       layername   : rec.get('getMapLayers')
      ,visibility  : rec.get('visibility')
      ,isBaseLayer : false
      ,projection  : proj3857
      ,opacity     : rec.get('opacity')
      ,scales      : [
         55468034.09273208   // ESRI Ocean zoom 3
        ,27734017.04636604
        ,13867008.52318302
        ,6933504.26159151
        ,3466752.130795755
        ,1733376.0653978775
        ,866688.0326989387
        ,433344.01634946937
        ,216672.00817473468
      ]
      ,time        : new Date().getTime()
      ,type        : 'png'
      ,getURL      : function (bounds) {
        bounds = this.adjustBounds(bounds);
        var res = this.map.getResolution();
        var x = Math.round((bounds.left - this.tileOrigin.lon) / (res * this.tileSize.w));
        // var y = Math.round((bounds.bottom - this.tileOrigin.lat) / (res * this.tileSize.h));
        var y = Math.round((this.maxExtent.top - bounds.top) / (res * this.tileSize.h));
        var z = this.serverResolutions != null ?
            OpenLayers.Util.indexOf(this.serverResolutions, res) :
            this.map.getZoom() + this.zoomOffset;
        z += map.baseLayer.minZoomLevel ? map.baseLayer.minZoomLevel : 0;
        var path = this.serviceVersion + "/" + this.layername + "/" + z + "/" + x + "/" + y + "." + this.type;
        var url = this.url;
        if (OpenLayers.Util.isArray(url)) {
            url = this.selectUrl(path, url);
        }
        return url + path + '?time=' + this.options.time;
      }
    }
  );
}

function addTileCache(rec) {
  return new OpenLayers.Layer.TileCache(
     rec.get('id')
    ,rec.get('getMapUrl')
    ,rec.get('getMapLayers')
    ,{
       visibility  : rec.get('visibility')
      ,isBaseLayer : false
      ,projection  : proj3857
      ,opacity     : rec.get('opacity')
      ,time        : new Date().getTime()
      ,type        : 'png'
      ,panel       : rec.get('panel')
      ,moreInfo    : rec.get('moreInfo')
      ,bbox        : rec.get('bbox')
      ,legend      : rec.get('legend')
    }
  );
}

function contains(bufferFeatures,g) {
  for (var i = 0; i < bufferFeatures.length; i++) {
    if (bufferFeatures[i].geometry.containsPoint(g)) {
      return true;
    }
  }
  return false;
}

//+ Jonas Raoni Soares Silva
//@ http://jsfromhell.com/array/shuffle [v1.0]
function shuffle(o) { //v1.0
  for(var j, x, i = o.length; i; j = parseInt(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
  return o;
};

function changeMode(id) {
  hideAllLayers();
  activeMode = id;
  var a = ['observations','weather','forecasts'];
  for (var i = 0; i < a.length; i++) {
    if (a[i] == id) {
      Ext.getCmp('mapTabPanel').setActiveTab(i);
    }
  }
  if (!Ext.getCmp(id + 'Panel')) {
    // no active mode (on init)
    return;
  }
  Ext.getCmp(id + 'Panel').show();
  // obs
  var lyr = map.getLayersByName('icon')[0].bbox = false;

  // fc
  if (id == 'forecasts') {
    var combo = Ext.getCmp('forecastMapsTypeComboBox');
    combo.fireEvent('select',combo,combo.getStore().getAt(combo.getStore().findExact('id',combo.getValue())));
    if (viewer != 'lite') {
      Ext.getCmp('forecastWindow').show();
    }
  }
  else if (id == 'weather') {
    var combo = Ext.getCmp('weatherMapsTypeComboBox');
    combo.fireEvent('select',combo,combo.getStore().getAt(combo.getStore().findExact('id',combo.getValue())));
    Ext.getCmp('forecastWindow').hide();
  }
  else {
    Ext.getCmp('forecastWindow').hide();
  }

  // byCatch doesn't rely on mode
  if (Ext.getCmp('byCatchTabPanel') && Ext.getCmp('byCatchTabPanel').getActiveTab().id == 'showByCatchPanel') {
    var combo = Ext.getCmp('byCatchMapsTypeComboBox');
    combo.fireEvent('select',combo,combo.getStore().getAt(combo.getStore().findExact('id',combo.getValue())));
  }

  map.getLayersByName('Watches, warnings, and advisories')[0].setVisibility(id == 'weather' && Ext.getCmp('wwaRadioGroupYes').getValue());
  map.getLayersByName('NHC storm tracks')[0].setVisibility(id == 'weather' && Ext.getCmp('wwaRadioGroupYes').getValue());
  map.getLayersByName('Marine zones')[0].setVisibility(id == 'weather' && Ext.getCmp('wwaRadioGroupYes').getValue());

  var l = map.getLayersByName('queryPt')[0];
  l.removeFeatures(l.features);

  map.events.triggerEvent('moveend');
}

function syncMapLegends(cb,lp) {
  if (!Ext.getCmp(cb)) {
    return;
  }
  var sto    = Ext.getCmp(cb).getStore();
  var rec    = sto.getAt(sto.findExact('id',Ext.getCmp(cb).getValue()));
  var layers = rec ? rec.get('wmsLegends') : [];

  if (viewer == 'lite' && layers.length == 0) {
    Ext.getCmp('bbarOceanConditionBbarPanel').hide();
    Ext.getCmp('bbar').doLayout();
  }
  else if (viewer == 'lite' && map.getLayersByName(layers[0])[0].visibility) {
    if (/forecast|weather/.test(cb)) {
      Ext.getCmp('bbarOceanConditionBbarPanel').show();
      Ext.getCmp('bbarOceanConditionDataType').update('<table class="bbarLabels"><tr><td>' + rec.get('liteLegendLabel') + '</td></tr></table>');
      Ext.getCmp('bbarOceanConditionLegend').update('<img width=175 src="' + rec.get('liteLegendImage') + '">');
    }
  }

  var lblTd  = [];
  var imgTd  = [];
  var infoTd = [];
  var titles = 0;
  for (var i = 0; i < layers.length; i++) {
    var l = map.getLayersByName(layers[i])[0];
    var p = OpenLayers.Util.getParameters(l.getFullRequestString({}));
    var params = {
       REQUEST : 'GetLegendGraphic'
      ,LAYER   : p['LAYERS']
    };
    if (p['STYLES'] && p['STYLES'].indexOf('boxfill/') >= 0) {
      params['PALETTE'] = p['STYLES'].replace('boxfill/','');
    }
    var ts = '';
    if (rec.get('historical')) {
      var a = [];
      for (var i = 0; i < rec.get('historical').length; i++) {
        a.push(rec.get('historical')[i][0] + ' : ' + dateToFriendlyString(rec.get('historical')[i][1]));
      }
      ts = '<br>' + a.join('<br>');
    }
    lblTd.push('<td style="text-align:center">' + (rec.get('showLegendTitle')[titles] ? rec.get('showLegendTitle')[titles] + ts : '') + '</td>');
    titles += rec.get('showLegendTitle')[titles] ? 1 : 0;
    var getMetadata = p['GetMetadata'] ? 'LAYER=' + p['LAYERS'] + '&TIME=' + (p['TIME'] ? p['TIME'] : '') + '&COLORSCALERANGE=' + p['COLORSCALERANGE'] + '&GetMetadata=' + encodeURIComponent(Ext.encode(l.legend)) + '&' : '';
    // exception for Natural color
    if (getMetadata == '' && /NaturalColor/.test(l.name)) {
      getMetadata = '&GetMetadata=' + encodeURIComponent(Ext.encode(l.legend)) + '&';
    }
    imgTd.push('<td style="text-align:center"><img src="' + baseUrl + 'getLegend.php?' + getMetadata + 'u=' + encodeURIComponent(l.getFullRequestString(params)) + '"></td>');
    infoTd.push('<td style="text-align:center"><a class="cleanLink" id="moreInfo' + l.name + '" href="javascript:moreInfo(\'' + rec.get('id') + '\',\'' + l.name + '\')">Learn more<br>about this data</a></td>');
  }
  var el = Ext.getCmp(lp);
  if (el.rendered) {
    el.update('<table class="blackText">'
      + (titles > 0 ? '<tr>' + lblTd.join('') + '</tr>' : '')
      + '<tr>' + imgTd.join('') + '</tr>'
      + '<tr>' + infoTd.join('') + '</tr>'
      + '</table>'
    );
    if (viewer == 'lite' && !/forecast|weather/.test(cb)) {
      // don't want more-info link here
      infoTd.pop();
      document.getElementById('byCatchLegend').innerHTML = '<table class="blackText">'
        + (titles > 0 ? '<tr>' + lblTd.join('') + '</tr>' : '')
        + '<tr>' + imgTd.join('') + '</tr>'
        + '<tr>' + infoTd.join('') + '</tr>'
        + '</table>';
    }
  }
  else {
    var html = '<table class="blackText">'
      + (titles > 0 ? '<tr>' + lblTd.join('') + '</tr>' : '')
      + '<tr>' + imgTd.join('') + '</tr>'
      + '</table>';
    el.contentToLoad = html;
    if (viewer == 'lite' && !/forecast|weather/.test(cb)) {
      document.getElementById('byCatchLegend').innerHTML = html;
    }
  }
}

function hideObsLegend() {
  if (viewer == 'lite') {
    Ext.getCmp('bbarOceanConditionBbarPanel').hide();
    return;
  }

  Ext.getCmp('obsLegendDivider').hide();
  Ext.getCmp('obsLegend').hide();
  Ext.getCmp('observationsPanel').get(0).setHeight(observationsPanelHeights.plain);
  var p = Ext.getCmp('weatherStationsTreePanel');
  delete p.anchorSpec;
  p.anchor = '100% -' + observationsPanelHeights.plain;
  Ext.getCmp('observationsPanel').doLayout();
}

function showObsLegend(o) {
  if (viewer == 'lite') {
    if (o == 'none') {
      Ext.getCmp('bbarOceanConditionBbarPanel').hide();
    }
    else {
      Ext.getCmp('bbarOceanConditionBbarPanel').show();
      Ext.getCmp('bbarOceanConditionDataType').update('<table class="bbarLabels"><tr><td>' + makeObsLegend(o).label + '</td></tr></table>');
      Ext.getCmp('bbarOceanConditionLegend').update('<img width=175 src="' + makeObsLegend(o).img + '">');
    }
    return; 
  }

  Ext.getCmp('obsLegend').get(0).update(makeObsLegend(o).html);
  Ext.getCmp('obsLegendDivider').show();
  Ext.getCmp('obsLegend').show();
  Ext.getCmp('observationsPanel').get(0).setHeight(observationsPanelHeights.plain + observationsPanelHeights.legendOffset);
  var p = Ext.getCmp('weatherStationsTreePanel');
  delete p.anchorSpec;
  p.anchor = '100% -' + (observationsPanelHeights.plain + observationsPanelHeights.legendOffset);
  Ext.getCmp('observationsPanel').doLayout();
}

function makeObsLegend(o) {
  var niceName = {
     winds      : 'Wind speed<br>(knots)'
    ,waves      : 'Wave height<br>(ft)'
    ,watertemp  : 'Water temp<br>(deg F)'
    ,waterlevel : 'Water level<br>(ft)'
  };

  if (niceName[o.toLowerCase()]) {
    return {
       html  : '<table class="blackText" style="width:100%"><tr><td style="width:90px" align=center>' + niceName[o.toLowerCase()] + '</td><td align=right><img width=204 height=34 src="' + obsLegendsPath + o.toLowerCase() + '.png"></td></tr></table>'
      ,label : niceName[o.toLowerCase()]
      ,img   : obsLegendsPath + o.toLowerCase() + '.png'
    };
  }
  else {
    return {
       html  : '<table class="blackText" style="width:100%"><tr><td style="width:90px" align=center>' + makeNiceTopObs(o).name + '</td></tr></table>'
      ,label : makeNiceTopObs(o).name
      ,img   : '<img src="img/blank.png">'
    };
  }
}

function cullObs(features,activeWeatherStations) {
  // take out any features that shouldn't appear at this zoom
  var okFeatures = [];
  for (var i = 0; i < features.length; i++) {
    var cull = false;
    for (var j = 0; j < obsCull.length; j++) {
      if (
        obsCull[j][0] == features[i].attributes.provider 
        && eval(obsCull[j][2])(features[i],obsCull[j][3],obsCull[j][4]) 
        && activeWeatherStations[obsCull[j][1]] 
        && obsCull[j][5] > map.getZoom()
      ) {
        cull = true;
        break;
      }
    }
    if (!cull) {
      okFeatures.push(features[i]);
    }
  }
  return okFeatures;
}

function mapLoadstartMask(name,panel) {
  if (viewer == 'lite') {
    document.getElementById('activity').style.visibility = 'visible';
  }

  if (panel && Ext.getCmp(panel + 'LegendPanel').getEl()) {
    loadingLayers[panel][name] = true;
    Ext.getCmp(panel + 'LegendPanel').getEl().mask('<table><tr><td>Updating...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');
  }
}

function mapLoadendUnmask(name,panel) {
  if (viewer == 'lite') {
    document.getElementById('activity').style.visibility = 'hidden';
  }

  if (panel) {
    delete loadingLayers[panel][name];
    var hits = 0;
    for (var i in loadingLayers[panel]) {
      hits++;
    }
    if (hits == 0 && Ext.getCmp(panel + 'LegendPanel').getEl()) {
      Ext.getCmp(panel + 'LegendPanel').getEl().unmask();
    }
  }
}

function linkMap(linkOnly) {
  var h = {
     mode          : activeMode
    ,mapCenter     : map.getCenter().transform(proj900913,proj4326).lon + ',' + map.getCenter().transform(proj900913,proj4326).lat
    ,mapZoom       : map.getZoom()
    ,basemap       : map.baseLayer.name
    ,bathyContours : (map.getLayersByName('Bathymetry contours')[0].visibility ? 'on' : 'off')
  };

  if (activeMode == 'forecasts') {
    h['fcLayer']    = Ext.getCmp('forecastMapsTypeComboBox').getValue() != 'None' ? Ext.getCmp('forecastMapsTypeComboBox').getValue().replace('&','%26') : '';
    h['fcContrast'] = Ext.getCmp(viewer == 'lite' ? 'contrastSlider' : 'forecastsVisibilitySlider').getValue();
  }
  else if (activeMode == 'weather') {
    h['wxLayer']    = Ext.getCmp('weatherMapsTypeComboBox').getValue() != 'None' ? Ext.getCmp('weatherMapsTypeComboBox').getValue().replace('&','%26') : '';
    h['wxContrast'] = Ext.getCmp(viewer == 'lite' ? 'contrastSlider' : 'weatherVisibilitySlider').getValue();
    h['wwa']        = Ext.getCmp('wwaRadioGroupYes').getValue() ? 'on' : 'off';
  }
  else if (activeMode == 'observations') {
    var lyr = map.getLayersByName('icon')[0];
    for (var k = 0; k < lyr.features.length; k++) {
      if (!lyr.features[k].hidden) {
        h['obs'] = varSummary(lyr.features[k],0.6).topObs;
        break;
      }
    }
  }

  if (
    (Ext.getCmp('byCatchTabPanel') && Ext.getCmp('byCatchTabPanel').getActiveTab().id == 'showByCatchPanel') 
    || (viewer == 'lite' && document.getElementById('byCatchLegend').style.visibility == 'visible')
  ) {
    h['byCatchLayer'] = Ext.getCmp('byCatchMapsTypeComboBox').getValue().replace('&','%26');
  }

  var params = [];
  for (var p in h) {
    params.push(p + '=' + h[p]);
  }

  if (!linkOnly) {
    var win = new Ext.Window({
       title           : document.title
      ,layout          : 'fit'
      ,width           : 300
      ,height          : 250
      ,constrainHeader : true
      ,modal           : true
      ,defaults        : {border : false}
      ,resizable       : false
      ,items           : new Ext.FormPanel({
         bodyStyle : 'padding:6'
        ,defaults  : {border : false}
        ,items     : [
          {
            html : '<table class="directionsTextNoAlign" width="100%"><tr><td>Choose one of the following methods to view or share your current configuration.</td></tr></table></td></tr></table>'
          }
          ,{html : '&nbsp;'}
          ,{
             layout : 'column'
            ,defaults  : {border : false}
            ,items  : [
               {columnWidth : 0.50,items : {xtype : 'container',autoEl : {tag : 'center'},items : {border : false,html : '<a target=_blank href="' + baseUrl + '?' + params.join('&') + '"><img width=64 height=64 src="img/bookmark64.png"></a><br>Right-click to<br>save as a bookmark'}}}
              ,{columnWidth : 0.50,items : {xtype : 'container',autoEl : {tag : 'center'},items : {border : false,html : '<a target=_blank href="mailto:?subject=' + document.title + '&body=' + baseUrl + '?' + encodeURIComponent(params.join('&').replace(/ /g,'%20')) + '"><img width=64 height=64 src="img/email64.png"></a><br>Email the<br>link to a friend'}}}
            ]
          }
        ]
      })
      ,buttons : [
        {
           text    : 'Close'
          ,handler : function() {
            win.hide();
          }
        }
      ]
    });
    win.show();
  }
  else {
    return baseUrl + '?' + params.join('&');
  }
}

function printMap(data,pLonLat) {
  if (new RegExp(/Google/).test(map.baseLayer.name)) {
    Ext.Msg.alert('Print error',"We're sorry, but Google basemaps are not eligible for printing.  Please select another basemap and try again.");
    return;
  }

  pokeMap();

  var layers   = {};
  var features = {};
  var legends  = {};
  for (var i = 0; i < map.layers.length; i++) {
    var lyr = map.layers[i];
    if (!lyr.isBaseLayer && lyr.getVisibility()) {
      if (lyr.DEFAULT_PARAMS) {
        layers[lyr.name] = [{name : lyr.name,url : lyr.getFullRequestString({WIDTH : map.div.style.width.replace('px',''),HEIGHT : map.div.style.height.replace('px',''),BBOX : map.getExtent()}),x : 0,y : 0,opacity : lyr.opacity}];
        if (lyr.name != 'NHC storm tracks') {
          if (activeMode == 'forecasts' && !legends['forecasts']) {
            legends['forecasts'] = '<table class="blackText" style="width:100%"><tr><td>' + Ext.getCmp('forecastMapsTypeComboBox').getValue() + '<br>' + document.getElementById('forecastMapsForecastText').innerHTML + '</td><td>' + document.getElementById('forecastsLegendPanel').innerHTML + '</td></tr></table>';
          }
          else if (activeMode == 'weather' && !legends['weather']) {
            var wx = '';
            if (Ext.getCmp('wwaRadioGroupYes').getValue()) {
              wx = '<tr><td>Hazards</td><td><img src="' + baseUrl + 'img/wwa.png"></td></tr>';
            }
            var cb  = Ext.getCmp('weatherMapsTypeComboBox');
            var sto = cb.getStore();
            var rec = sto.getAt(sto.findExact('id',cb.getValue()));
            legends['weather'] = '<table class="blackText" style="width:100%"><tr><td>' + rec.get('id') + '</td><td>' + document.getElementById('weatherLegendPanel').innerHTML + '</td></tr><tr><td>&nbsp;</td></tr>' + wx + '</table>';
          }
          if (Ext.getCmp('byCatchTabPanel') && Ext.getCmp('byCatchTabPanel').getActiveTab().id == 'showByCatchPanel' && !legends['byCatch']) {
            legends['byCatch'] = '<table class="blackText" style="width:100%"><tr><td>Bycatch</td><td>' + document.getElementById('showByCatchLegendPanel').innerHTML + '</td></tr></table>';
          }
        }
      }
      else if (lyr.grid) {
        var a = [];
        for (tilerow in lyr.grid) {
          for (tilei in lyr.grid[tilerow]) {
            var tile = lyr.grid[tilerow][tilei];
            if (tile.bounds) {
              var url      = lyr.getURL(tile.bounds);
              var position = tile.position;
              a.push({url : url,x : position.x,y : position.y,opacity : lyr.opacity});
            }
          }
        }
        layers[lyr.name] = a;
      }
      else if (lyr.name == 'icon') {
        features[lyr.name] = [];
        for (var k = 0; k < lyr.features.length; k++) {
          if (!lyr.features[k].hidden) {
            var s   = varSummary(lyr.features[k],0.6);
            if (s.url != 'img/blank.png') {
              var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(lyr.features[k].geometry.x,lyr.features[k].geometry.y));
              features[lyr.name].push([
                 pix.x
                ,pix.y
                ,baseUrl + s.url
                ,s.w
                ,s.h
              ]);
              if (!legends['obs']) {
                legends['obs'] = makeObsLegend(s.leg).html.replace(obsLegendsPath,baseUrl + obsLegendsPath).replace(/style="width:90px" align=center|align=right/g,'');
              }
            }
          }
        }
      }
      else if (lyr.name == 'queryPt' && data) {
        features[lyr.name] = [];
        for (var k = 0; k < lyr.features.length; k++) {
          var pix = map.getPixelFromLonLat(new OpenLayers.LonLat(lyr.features[k].geometry.x,lyr.features[k].geometry.y));
          features[lyr.name].push([
             pix.x
            ,pix.y
            ,baseUrl + 'img/Delete-icon.png'
            ,16
            ,16
          ]);
        }
      }
    }
  }

  var legend = [];
  for (var i in legends) {
    legend.push(legends[i]);
  }
  legend = legend.join('<br><br>');

  var basemap = [];
  var baseLayer = map.baseLayer;
  for (tilerow in baseLayer.grid) {
    for (tilei in baseLayer.grid[tilerow]) {
      var tile = baseLayer.grid[tilerow][tilei];
      if (tile.bounds) {
        var url      = baseLayer.getURL(tile.bounds);
        var position = tile.position;
        basemap.push({url : url,x : position.x,y : position.y});
      }
    }
  }

  Ext.MessageBox.show({
     title        : 'Please wait'
    ,msg          : 'Generating template...'
    ,width        : 300
    ,wait         : true
    ,waitConfig   : {interval : 200}
  });
  checkPrintTimer = setTimeout('printErrorAlert()',30000);

  OpenLayers.Request.issue({
     method  : 'POST'
    ,url     : 'print.php'
    ,headers : {'Content-Type' : 'application/x-www-form-urlencoded'}
    ,data    : OpenLayers.Util.getParameterString({
       lyr     : Ext.encode(layers)
      ,ftr     : Ext.encode(features)
      ,bm      : Ext.encode(basemap)
      ,w       : map.div.style.width.replace('px','')
      ,h       : map.div.style.height.replace('px','')
      ,leg     : legend.replace(/Learn more<br>about this data/ig,'')
      ,t       : new Date()
      ,title   : document.title
      ,base    : baseUrl
      ,data    : Ext.encode(data)
      ,bbox    : Ext.encode(map.getExtent().transform(proj900913,proj4326).toArray())
      ,pLonLat : (pLonLat ? encodeURIComponent(pLonLat) : '')
    })
    ,callback : function(r) {
      Ext.MessageBox.hide();
      clearTimeout(checkPrintTimer);
      Ext.Msg.buttonText.ok = 'Close';
      Ext.Msg.alert('Template complete','Click <a target=_blank href="tmp/' + r.responseText + '">here</a> for a printer-friendly page.');
      Ext.Msg.buttonText.ok = 'OK';
    }
  });
}

function printErrorAlert() {
  Ext.MessageBox.hide();
  Ext.Msg.alert('Print/save error',"We're sorry, but a print error has occured.  Please try again.");
}

function runQuery() {
  var sto = new Ext.data.XmlStore({
    proxy       : new Ext.data.HttpProxy({
       method  : 'POST'
      ,url     : 'post.php?t=' + new Date().getTime() + '&ns=csw|gmi|gml|srv|gmd|gco&url=' + encodeURIComponent('http://user:glos@64.9.200.121:8984/rest/glos')
      ,timeout : 120000
    })
    ,record        : 'gmd_MD_Metadata'
    ,totalProperty : 'csw_SearchResults@numberOfRecordsMatched'
    ,fields        : [
       {name : 'title'          ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_citation > gmd_CI_Citation > gmd_title > gco_CharacterString'}
      ,{name : 'provider'       ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_citation > gmd_CI_Citation > gmd_citedResponsibleParty > gmd_CI_ResponsibleParty > gmd_organisationName > gco_CharacterString'}
      ,{name : 'abstract'       ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_abstract > gco_CharacterString'}
      ,{name : 'cswId'          ,mapping : 'gmd_fileIdentifier > gco_CharacterString'}
      ,{name : 'bboxWest'       ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_extent > gmd_EX_Extent > gmd_geographicElement > gmd_EX_GeographicBoundingBox > gmd_westBoundLongitude > gco_Decimal'}
      ,{name : 'bboxEast'       ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_extent > gmd_EX_Extent > gmd_geographicElement > gmd_EX_GeographicBoundingBox > gmd_eastBoundLongitude > gco_Decimal'}
      ,{name : 'bboxSouth'      ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_extent > gmd_EX_Extent > gmd_geographicElement > gmd_EX_GeographicBoundingBox > gmd_southBoundLatitude > gco_Decimal'}
      ,{name : 'bboxNorth'      ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_extent > gmd_EX_Extent > gmd_geographicElement > gmd_EX_GeographicBoundingBox > gmd_northBoundLatitude > gco_Decimal'}
      ,{name : 'minT'           ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_extent > gmd_EX_Extent > gmd_temporalElement > gmd_EX_TemporalExtent > gmd_extent > gml_TimePeriod > gml_beginPosition'}
      ,{name : 'maxT'           ,mapping : 'gmd_identificationInfo > gmd_MD_DataIdentification > gmd_extent > gmd_EX_Extent > gmd_temporalElement > gmd_EX_TemporalExtent > gmd_extent > gml_TimePeriod > gml_endPosition'}
      ,{name : 'services'       ,convert : (function(){
        return function(v,n) {
          return new Ext.data.XmlReader({
             record : 'gmd_identificationInfo > srv_SV_ServiceIdentification'
            ,fields : [
               {name : 'category',mapping : '@id'}
              ,{name : 'details' ,convert : (function(){
                return function(v,n) {
                  return new Ext.data.XmlReader({
                     record : 'srv_containsOperations > srv_SV_OperationMetadata'
                    ,fields : [
                       {name : 'name'    ,mapping : 'srv_operationName > gco_CharacterString'}
                      ,{name : 'url'     ,mapping : 'srv_connectPoint > gmd_CI_OnlineResource > gmd_linkage > gmd_URL'}
                    ]
                  }).readRecords(n).records;
                }
              })()}
            ]
          }).readRecords(n).records;
        }
      })()}
      ,{name : 'dimensions'     ,convert : (function(){
        return function(v,n) {
          return new Ext.data.XmlReader({
             record : 'gmd_contentInfo > gmi_MI_CoverageDescription > gmd_dimension'
            ,fields : [
               {name : 'name'    ,mapping : 'gmd_MD_Band > gmd_descriptor > gco_CharacterString'}
              ,{name : 'niceName',mapping : 'gmd_MD_Band > gmd_sequenceIdentifier > gco_MemberName > gco_aName > gco_CharacterString'}
            ]
          }).readRecords(n).records;
        }
      })()}
    ]
    ,listeners  : {
      beforeload : function(sto,o) {
        Ext.getCmp('searchResultsGridPanel').getEl().mask('<table><tr><td>Loading...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');
        activeSearches++;
        sto.setBaseParam('xmlData',buildFilter(o.params.limit,o.params.start));
        _gaq.push(['_trackEvent','Search',Ext.getCmp('anyTextSearchField').getValue()]);
      }
      ,load      : function(sto) {
        activeSearches--;
        if (activeSearches <= 0) {
          Ext.getCmp('searchResultsGridPanel').getEl().unmask();
        }
        var c = sto.getTotalCount();
        if (c == 0) {
          Ext.getCmp('searchResultsWin').setTitle('Search results : No results found');
        }
        else {
          Ext.getCmp('searchResultsWin').setTitle('Search results : ' + c  + ' result(s) found');
        }
      }
    }
  });

  var c = Ext.getCmp('searchResultsGridPanel');
  if (!c) {
    var el = document.getElementById('map');
    var w  = 580;
    var h  = 440;
    if (el) {
      w = el.style.width.split('px')[0] * 0.75;
      h = el.style.height.split('px')[0] * 0.75;
    }

    new Ext.Window({
       title           : 'Search results'
      ,id              : 'searchResultsWin'
      ,layout          : 'fit'
      ,width           : Math.max(580,w)
      ,minWidth        : 580
      ,height          : h
      ,constrainHeader : true
      ,tbar            : [
        new Ext.ButtonGroup({
           title   : 'Keywords'
          ,columns : 2
          ,items   : [
            new Ext.ux.form.SearchField({
               emptyText       : 'Enter keywords to find data.'
              ,cls             : (Ext.isChrome || Ext.isSafari) ? 'chromeInput' : ''
              ,value           : Ext.getCmp('anyTextSearchField').getValue()
              ,width           : Math.max(580,w) - 18 - 20 - 195 - 235
              ,border          : false
              ,id              : 'anyTextSearchFieldPopup'
              ,paramName       : 'anyText'
              ,wrapFocusClass  : ''
              ,hasSearch       : true
              ,hideTrigger1    : false
              ,onTrigger1Click : function() {
                if(this.hasSearch){
                    this.reset();
                    // having a tough time w/ the focus, so force a reset for emptyText
                    this.setRawValue(this.emptyText);
                    this.el.addClass(this.emptyClass);
                    var o = {start: 0};
                    if (this.store) {
                      this.store.baseParams = this.store.baseParams || {};
                      this.store.baseParams[this.paramName] = '';
                      this.store.reload({params:o});
                    }
                    this.triggers[0].hide();
                    this.hasSearch = false;
                }
              }
              ,onTrigger2Click : function() {
                var v = this.getRawValue();
                if(v.length < 1){
                    this.onTrigger1Click();
                    return;
                }
                var o = {start: 0};
                if (this.store) {
                  this.store.baseParams = this.store.baseParams || {};
                  this.store.baseParams[this.paramName] = v;
                  this.store.reload({params:o});
                }
                Ext.getCmp('anyTextSearchField').setRawValue(this.getRawValue());
                runQuery();
                this.hasSearch = true;
                this.triggers[0].show();
              }
            })
            ,{
               icon    : 'img/help16.png'
              ,id      : 'sampleSearchButton'
              ,handler : function() {
                var tt = Ext.getCmp('sampleSearchTooltip');
                if (!tt) {
                  tt = new Ext.ToolTip({
                     id           : 'sampleSearchTooltip'
                    ,target       : Ext.getCmp('sampleSearchButton').getEl()
                    ,title        : 'Sample search strings'
                    ,bodyStyle    : "border:1px solid #99BBE8"
                    ,html         : '<table style="width:385px" class="popup"><tr><td>Samples will be coming soon.</td><td></tr></table>' // '<table style="width:385px" class="popup"><tr><td>ndbc</td><td>Find all entries that contain <b>ndbc</b>.</td></tr><tr><td>ndbc&nbsp;sst</td><td>Find all entries that contain both <b>ndbc</b> and <b>sst</b>.</td></tr><tr><td>"Devils&nbsp;Island"</td><td>Find all entries that contain the complete string <b>Devils Island</b>.</td></tr><tr><td>"Devils&nbsp;Island"&nbsp;sst</td><td>Find all entries that contain the complete string <b>Devils Island</b> as well as <b>sst</b>.</td></tr></table>'
                    ,width        : 400
                    ,anchor       : 'left'
                    ,closable     : true
                    ,autoHide     : false
                  });
                }
                tt.show();
              }
            }
          ]
        })
        ,'->'
        ,new Ext.ButtonGroup({
            title  : 'Optional search filters'
          ,columns : 4
          ,items   : [
            {
               id           : 'restrictGeoSearchButton'
              ,tooltip      : 'Restrict the search to a specific area of interest'
              ,text         : 'Select an area of interest filter'
              ,cls          : 'leftAlignButton'
              ,allowDepress : false
              ,icon         : 'img/zoom.png'
              ,width        : 185
              ,menu         : {id : 'restrictGeoSearchMenu',items : [
                {
                   text    : 'No area restriction'
                  ,group   : 'greatLakes'
                  ,checked : true
                  ,handler : function(el) {
                    Ext.getCmp('restrictGeoSearchButton').setText(el.text);
                    runQuery();
                  }
                }
                ,'-'
                ,{
                   text    : 'Explorer boundaries'
                  ,group   : 'greatLakes'
                  ,checked : true
                  ,handler : function(el) {
                    Ext.getCmp('restrictGeoSearchButton').setText('Restrict to ' + el.text);
                    runQuery();
                  }
                }
                ,'-'
              ]}
              ,listeners    : {afterrender : function(b) {
                for (var i = 0; i < geo.greatLakes.features.length; i++) {
                  Ext.getCmp('restrictGeoSearchMenu').add({
                     text    : geo.greatLakes.features[i].attributes.name
                    ,group   : 'greatLakes'
                    ,checked : false
                    ,handler : function(el) {
                      Ext.getCmp('restrictGeoSearchButton').setText('Restrict to ' + el.text);
                      runQuery();
                    }
                  });
                }
              }}
            }
            ,{
               id           : 'restrictTimeSearchButton'
              ,tooltip      : 'Restrict the search to a specific date range of interest'
              ,text         : 'Select a time filter'
              ,cls          : 'leftAlignButton'
              ,allowDepress : false
              ,icon         : 'img/calendar.png'
              ,width        : 225
              ,menu         : {id : 'restrictTimeSearchMenu',items : [
                {
                   text    : 'No date range restriction'
                  ,group   : 'timeOptions'
                  ,checked : true
                  ,handler : function(el) {
                    delete map.searchBeginDate;
                    delete map.searchEndDate;
                    Ext.getCmp('restrictTimeSearchButton').setText(el.text);
                    runQuery();
                  }
                }
                ,'-'
                ,{
                   text    : 'Until this date...'
                  ,group   : 'timeOptions'
                  ,checked : true
                  ,handler : function(el) {
                    var dWin = new Ext.Window({
                       title     : 'Time restriction option(s)'
                      ,modal     : true
                      ,width     : 240
                      ,layout    : 'form'
                      ,labelSeparator : ''
                      ,x         : Ext.getCmp('restrictTimeSearchButton').getPosition()[0]
                      ,y         : Ext.getCmp('restrictTimeSearchButton').getPosition()[1]
                      ,bodyStyle : 'padding:6;background:white'
                      ,constrainHeader : true
                      ,items     : new Ext.form.DateField({
                         fieldLabel : 'Until this date'
                        ,id         : 'endDate'
                        ,allowBlank : false
                        ,value      : map.searchEndDate ? map.searchEndDate : new Date()
                      })
                      ,buttons  : [
                         {text : 'OK',handler : function() {
                           if (Ext.getCmp('endDate').isValid()) {
                             Ext.getCmp('restrictTimeSearchButton').setText('Until ' + Ext.getCmp('endDate').getValue().format("mmm d, yyyy"));
                             delete map.searchBeginDate;
                             map.searchEndDate = Ext.getCmp('endDate').getValue();
                             runQuery();
                             dWin.close();
                           }
                         }}
                        ,{text : 'Cancel',handler : function() {dWin.close()}}
                      ]
                    });
                    dWin.show();
                  }
                }
                ,{
                   text    : 'Between these dates...'
                  ,group   : 'timeOptions'
                  ,checked : true
                  ,handler : function(el) {
                    var dWin = new Ext.Window({
                       title     : 'Time restriction option(s)'
                      ,modal     : true
                      ,width     : 240
                      ,layout    : 'form'
                      ,labelSeparator : ''
                      ,x         : Ext.getCmp('restrictTimeSearchButton').getPosition()[0]
                      ,y         : Ext.getCmp('restrictTimeSearchButton').getPosition()[1]
                      ,bodyStyle : 'padding:6;background:white'
                      ,constrainHeader : true
                      ,items     : [
                        new Ext.form.DateField({
                           fieldLabel : 'Between'
                          ,id         : 'beginDate'
                          ,allowBlank : false
                          ,value      : map.searchBeginDate ? map.searchBeginDate : new Date()
                        })
                        ,new Ext.form.DateField({
                           fieldLabel : 'And'
                          ,id         : 'endDate'
                          ,allowBlank : false
                          ,value      : map.searchEndDate ? map.searchEndDate : new Date()
                        })
                      ]
                      ,buttons  : [
                         {text : 'OK',handler : function() {
                           if (Ext.getCmp('beginDate').isValid() && Ext.getCmp('endDate').isValid()) {
                             Ext.getCmp('restrictTimeSearchButton').setText('Between ' + Ext.getCmp('beginDate').getValue().format("mmm d, yyyy") + ' & ' + Ext.getCmp('endDate').getValue().format("mmm d, yyyy"));
                             map.searchBeginDate = Ext.getCmp('beginDate').getValue();
                             map.searchEndDate = Ext.getCmp('endDate').getValue();
                             runQuery();
                             dWin.close();
                           }
                         }}
                        ,{text : 'Cancel',handler : function() {dWin.close()}}
                      ]
                    });
                    dWin.show();
                  }
                }
                ,{
                   text    : 'After this date...'
                  ,group   : 'timeOptions'
                  ,checked : true
                  ,handler : function(el) {
                    var dWin = new Ext.Window({
                       title     : 'Time restriction option(s)'
                      ,modal     : true
                      ,width     : 240
                      ,layout    : 'form'
                      ,labelSeparator : ''
                      ,x         : Ext.getCmp('restrictTimeSearchButton').getPosition()[0]
                      ,y         : Ext.getCmp('restrictTimeSearchButton').getPosition()[1]
                      ,bodyStyle : 'padding:6;background:white'
                      ,constrainHeader : true
                      ,items     : new Ext.form.DateField({
                         fieldLabel : 'After this date'
                        ,id         : 'beginDate'
                        ,allowBlank : false
                        ,value      : map.searchBeginDate ? map.searchBeginDate : new Date()
                      })
                      ,buttons  : [
                         {text : 'OK',handler : function() {
                           if (Ext.getCmp('beginDate').isValid()) {
                             Ext.getCmp('restrictTimeSearchButton').setText('After ' + Ext.getCmp('beginDate').getValue().format("mmm d, yyyy"));
                             map.searchBeginDate = Ext.getCmp('beginDate').getValue();
                             delete map.searchEndDate;
                             runQuery();
                             dWin.close();
                           }
                         }}
                        ,{text : 'Cancel',handler : function() {dWin.close()}}
                      ]
                    });
                    dWin.show();
                  }
                }
              ]}
            }
          ]
        })
      ]
      ,bbar            : new Ext.PagingToolbar({
         pageSize    : searchLimitPerPage
        ,store       : sto
        ,id          : 'searchResultsPagingToolbar'
        ,displayInfo : true
        ,displayMsg  : 'Displaying results {0} - {1} of {2}'
        ,emptyMsg    : 'No results to display'
        // override the following because start is 1-based instead of extjs's 0-based default
        ,moveFirst   : function() {
          this.doLoad(1);
        }
        ,onLoad : function(store, r, o){
            if(!this.rendered){
                this.dsLoaded = [store, r, o];
                return;
            }
            var p = this.getParams();
            this.cursor = (o.params && o.params[p.start]) ? o.params[p.start] : 1;
            var d = this.getPageData(), ap = d.activePage, ps = d.pages;

            this.afterTextItem.setText(String.format(this.afterPageText, d.pages));
            this.inputItem.setValue(ap);
            this.first.setDisabled(ap == 1);
            this.prev.setDisabled(ap == 1);
            this.next.setDisabled(ap == ps);
            this.last.setDisabled(ap == ps);
            this.refresh.enable();
            this.updateInfo();
            this.fireEvent('change', this, d);
        }
        ,updateInfo : function(){
            if(this.displayItem){
                var count = this.store.getCount();
                var msg = count == 0 ?
                    this.emptyMsg :
                    String.format(
                        this.displayMsg,
                        this.cursor, this.cursor+count - 1, this.store.getTotalCount()
                    );
                this.displayItem.setText(msg);
            }
        }
        ,getPageData : function(){
            var total = this.store.getTotalCount();
            return {
                total : total,
                activePage : Math.ceil((this.cursor+this.pageSize - 1)/this.pageSize),
                pages :  total < this.pageSize ? 1 : Math.ceil(total/this.pageSize)
            };
        }
      })
      ,items           : new Ext.grid.GridPanel({
         id      : 'searchResultsGridPanel'
        ,cls     : 'chromeNoHorizontalScrollbar'
        ,store   : sto
        ,columns : [
          {dataIndex : 'title',id : 'title',renderer : function(val,p,rec) {
            var data = [];
            var dimensions = rec.get('dimensions');
            var dimSort = [];
            var niceName2Name = {};
            for (var j = 0; j < dimensions.length; j++) {
              dimSort.push(dimensions[j].get('niceName'));
              niceName2Name[dimensions[j].get('niceName')] = dimensions[j].get('name');
            }
            dimSort.sort(function(a,b) {
              return a.toLowerCase().localeCompare(b.toLowerCase());
            });
            for (var j = 0; j < dimSort.length; j++) {
              var id = Ext.id();
              data.push([
                dimSort[j],niceName2Name[dimSort[j]]
              ]);
            }

            function createComboBox(searchVal,id,searchRec,servicesRec,data) {
              new Ext.form.ComboBox({
                store          : new Ext.data.ArrayStore({
                   fields : ['title','name']
                  ,data   : data
                  ,filter : function(property,value) {
                    if (value == '') {
                      return true;
                    }
                    this.filterBy(function(record,id) {
                      return record.get('title').toLowerCase().indexOf(value.toLowerCase()) >= 0
                    });
                  }
                })
                ,renderTo       : 'combo.' + id
                ,width          : 370
                ,forceSelection : true
                ,triggerAction  : 'all'
                ,emptyText      : 'Enter part of an observation name or click the down-arrow.'
                ,cls            : Ext.isChrome ? 'chromeInput' : ''
                ,selectOnFocus  : true
                ,mode           : 'local'
                ,displayField   : 'title'
                ,valueField     : 'name'
                ,listeners      : {select : function(cb,rec,i) {
                  sosGetObs(
                     searchVal
                    ,rec.get('name')
                    ,servicesRec.get('url').replace(/observedProperty=([^&]*)/i,'observedProperty=' + rec.get('name'))
                    ,[searchRec.get('bboxWest'),searchRec.get('bboxSouth')]
                    ,searchRec.get('provider')
                    ,'combo.' + id
                    ,searchRec.get('minT')
                    ,searchRec.get('maxT')
                  );
                }}
              });
            }

            var svc = [];
            var svcTOC = [];
            var services = rec.get('services');
            for (var i = 0; i < services.length; i++) {
              var details = services[i].get('details');
              for (var j = 0; j < details.length; j++) {
                var p = OpenLayers.Util.getParameters(details[j].get('url'));
                if (new RegExp(/getcapabilities/i).test(p['request']) && new RegExp(/wms/i).test(p['service'])) {
                  svc.push('<a href="javascript:wmsGetCaps(\'' + encodeURIComponent(val) + '\',\'' + encodeURIComponent(details[j].get('url')) + '\')">' + '<img style="margin-bottom:-3px" width=16 height=16 src="img/layers_map.png">' + '</a> ' + '<a href="javascript:wmsGetCaps(\'' + encodeURIComponent(val) + '\',\'' + encodeURIComponent(details[j].get('url')) + '\')">' + 'Preview data on map.' + '</a>');
                }
                else if (new RegExp(/getobservation/i).test(p['request']) && new RegExp(/sos/i).test(p['service'])) {
                  var id = Ext.id();
                  svc.push('<img style="margin-bottom:-3px" width=16 height=16 src="img/chart16.png"> Select an observation to preview from the list below:');
                  svc.push('<img height=5 src="img/blank.png"');
                  svc.push('<div id="combo.' + id + '"></div>');
                  createComboBox.defer(100,this,[val,id,rec,details[j],data]);
                }
                svcTOC.push( 
                  '<a target=_blank href="' + details[j].get('url')  + (/opendap/i.test(details[j].get('name')) ? '.html' : '') + '">'
                  + (new RegExp(services[i].get('category')).test(details[j].get('name')) ? details[j].get('name') : (services[i].get('category') + ' ' + details[j].get('name')))
                  + '</a>'
                );
              }
            }
            svcTOC.sort(function(a,b) {
              return a.toLowerCase().localeCompare(b.toLowerCase());
            });
            var d = '';
            if (rec.get('minT') != '' && rec.get('maxT') != '') {
              if (isoDateToDate(rec.get('minT')).format('mmm d, yyyy') == isoDateToDate(rec.get('maxT')).format('mmm d, yyyy')) {
                d = '<br><br>' + isoDateToDate(rec.get('minT')).format('mmm d, yyyy');
              }
              else if (isoDateToDate(rec.get('minT')).format('yyyy') == isoDateToDate(rec.get('maxT')).format('yyyy')) {
                d = '<br><br>' + isoDateToDate(rec.get('minT')).format('mmm d') + ' - ' + isoDateToDate(rec.get('maxT')).format('mmm d, yyyy');
              }
              else {
                d = '<br><br>' + isoDateToDate(rec.get('minT')).format('mmm d, yyyy') + ' - ' + isoDateToDate(rec.get('maxT')).format('mmm d, yyyy');
              }
            }
            else if (rec.get('minT') != '') {
              d = '<br><br>from ' + isoDateToDate(rec.get('minT')).format('mmm d, yyyy');
            }
            else if (rec.get('maxT') != '') {
              d = '<br><br>to ' + isoDateToDate(rec.get('maxT')).format('mmm d, yyyy');
            }
            return '<b><a href="xsl2html.php?xsl=' + xsl + '&id=' + encodeURIComponent(rec.get('cswId')) + '&url=' + encodeURIComponent('http://user:glos@64.9.200.121:8984/rest/glos') + '" target=_blank>' + (val != '' ? val : 'Title unavailable') + '</a></b><p><br>' + rec.get('abstract') + d + '</p>' + (svcTOC.length > 0 ? '<p><br>Data service(s) : ' + svcTOC.join(' | ') + '</p>' : '') + (svc.length > 0 ? '<p><br>' + svc.join('<br>') + '</p>' : '');
          }}
          ,{width : 142,renderer : function(val,p,rec) {
            var bbox = [rec.get('bboxWest'),rec.get('bboxSouth'),rec.get('bboxEast'),rec.get('bboxNorth')];
            if (bbox[0] == '') {
              return '<img width=128 height=128 src="img/worldError.png" title="Boundaries not found" alt="Boundaries not found">';
            }
            else if (bbox[0] == bbox[2] && bbox[1] == bbox[3]) {
              return '<img style="border:1px solid #99BBE8" width=128 height=128 src="https://maps.googleapis.com/maps/api/staticmap?key=AIzaSyBuB8P_e6vQcucjnE64Kh2Fwu6WzhMXZzI&markers=' + bbox[1] + ',' + bbox[0] + '&zoom=10&size=128x128&sensor=false" title="Data location" alt="Data location">';
            }
            else if (bbox[0] == -180 && bbox[1] == -90 && bbox[2] == 180 && bbox[3] == 90) {
              return '<img width=128 height=128 src="img/world.png" title="Global boundaries" alt="Global boundaries">';
            }
            else {
              return '<img style="border:1px solid #99BBE8" width=128 height=128 src="https://maps.googleapis.com/maps/api/staticmap?key=AIzaSyBuB8P_e6vQcucjnE64Kh2Fwu6WzhMXZzI&path=weight:1|fillcolor:0x0000AA11|color:0x0000FFBB|' + bbox[1] + ',' + bbox[0] + '|' + bbox[1] + ',' + bbox[2] + '|' + bbox[3] + ',' + bbox[2] + '|' + bbox[3] + ',' + bbox[0] + '|' + bbox[1] + ',' + bbox[0] + '&size=128x128&sensor=false" title="Data boundaries" alt="Data boundaries">';
            }
          }}
        ]
        ,border           : false
        ,autoExpandColumn : 'title'
        ,hideHeaders      : true
        ,disableSelection : true
      })
      ,listeners : {
        show : function() {
          Ext.getCmp('anyTextSearchField').disable();
        }
        ,hide : function() {
          Ext.getCmp('anyTextSearchField').enable();
          delete map.searchBeginDate;
          delete map.searchEndDate;
        }
        ,afterrender : function() {
          this.addListener('resize',function(win,w,h) {
            Ext.getCmp('anyTextSearchFieldPopup').setWidth(win.getInnerWidth() - 4 - 18 - 195 - 235);
          });
          Ext.getCmp('searchResultsGridPanel').getStore().load({
            params : {start : searchStart,limit : searchLimitPerPage}
          });
        }
      }
    }).show();
  }
  else {
    c.getStore().load();
  }
}

function buildFilter(limit,start) {
  if (typeof limit != 'number') {
    limit = searchLimitPerPage;
  }
  if (typeof start != 'number') {
    start = searchStart;
  }
 
  var filter = catalogQueryXML.replace('___LIMIT___',limit).replace('___START___',start);

  if (Ext.getCmp('anyTextSearchField').getValue() != '') {
    filter = filter.replace('___TEXTSEARCH___',1).replace('___ANYTEXT___',Ext.getCmp('anyTextSearchField').getValue());
  }
  else {
    filter = filter.replace('___TEXTSEARCH___',0).replace('___ANYTEXT___','');
  }

  var bbox = [0,0,0,0];
  if (new RegExp(/Explorer boundaries/).test(Ext.getCmp('restrictGeoSearchButton').getText())) {
    bbox = map.getExtent().transform(proj900913,proj4326).toArray();
  }
  else {
    var f;
    for (var i = 0; i < geo.greatLakes.features.length; i++) {
      if (geo.greatLakes.features[i].attributes.name == Ext.getCmp('restrictGeoSearchButton').getText().replace('Restrict to ','')) {
        f = geo.greatLakes.features[i].clone();
      }
    }
    if (f) {
      bbox = f.geometry.getBounds().transform(proj900913,proj4326).toArray();
    }
  }

  var geoSearch = false;
  if (Ext.getCmp('restrictGeoSearchButton')) {
    geoSearch = new RegExp(/Lake|Explorer boundaries/).test(Ext.getCmp('restrictGeoSearchButton').getText());
  }
  filter = filter.replace('___GEOSEARCH___',geoSearch ? 1 : 0).replace('___WEST___',bbox[0]).replace('___SOUTH___',bbox[1]).replace('___EAST___',bbox[2]).replace('___NORTH___',bbox[3]);

  var temporalSearch = typeof map.searchBeginDate != 'undefined' || typeof map.searchEndDate != 'undefined';
  var d0             = '1800-01-01T00:00:00';
  var d1             = '1800-01-01T00:00:00';
  if (typeof map.searchBeginDate != 'undefined' && typeof map.searchEndDate != 'undefined') {
    d0 = map.searchBeginDate.format('isoUtcDateTime').replace('Z','');
    d1 = map.searchEndDate.format('isoUtcDateTime').replace('Z','');
  }
  else if (typeof map.searchBeginDate != 'undefined') {
    d0 = map.searchBeginDate.format('isoUtcDateTime').replace('Z','');
    d1 = new Date(9999,01,01).format('isoUtcDateTime').replace('Z','');
  }
  else if (typeof map.searchEndDate != 'undefined') {
    d0 = new Date(0).format('isoUtcDateTime').replace('Z','');
    d1 = map.searchEndDate.format('isoUtcDateTime').replace('Z','');
  }
  filter = filter.replace('___TEMPORALSEARCH___',temporalSearch ? 1 : 0).replace('___TSTART___',d0).replace('___TEND___',d1); 

  return filter;
}

function startChat(conversationId) {
  var c = Ext.getCmp('chatGridPanel');
  if (c && typeof(conversationId) == 'number') {
    var sto = c.getStore();
    if (sto.baseParams) {
      delete sto.baseParams.newComment;
    }
    sto.load({params : {getConversation : conversationId}});
  }
  if (!c) {
    var el = document.getElementById('map');
    var w  = 580;
    var h  = 440;
    if (el) {
      w = el.style.width.split('px')[0] * 0.75;
      h = el.style.height.split('px')[0] * 0.75;
    }

    var sto = new Ext.data.GroupingStore({
      reader     : new Ext.data.JsonReader({
         idProperty    : 'id'
        ,root          : 'rows'
        ,totalProperty : 'results'
        ,fields        : [ 
           {name : 'title'}
          ,{name : 'description'}
          ,{name : 'date',type : 'date',dateFormat : 'Y-m-d H:i',sortable : true}
          ,{name : 'user'}
          ,{name : 'err'}
          ,{name : 'singleC'}
        ]
      })
      ,proxy      : new Ext.data.HttpProxy({
         url    : 'chat.php'
        ,method : 'GET'
      })
      ,remoteSort : true
      ,groupField : 'title'
      ,autoLoad   : true
      ,listeners  : {
        beforeload : function(sto) {
          sto.setBaseParam('limit',chatLimitPerPage);
        }
        ,load      : function(sto) {
          if (sto.getCount() > 0) {
            if (sto.getAt(0).get('singleC')) {
              Ext.getCmp('conversationBackButton').show();
              Ext.getCmp('conversationDirections').hide();
            }
            else {
              Ext.getCmp('conversationBackButton').hide();
              Ext.getCmp('conversationDirections').show();
            }
            if (new RegExp(/duplicate key.*chat_q_text_key/i).test(sto.getAt(0).get('err'))) {
              Ext.Msg.alert('Conversation error','This conversation already exists.  Please try searching, or rewrite your conversation starter.');
            }
          }
          sto.setBaseParam('getConversation',null);
        }
      }
    });

    new Ext.Window({
       title           : 'Comments'
      ,layout          : 'fit'
      ,width           : Math.max(635,w)
      ,minWidth        : 635
      ,height          : h
      ,constrainHeader : true
      ,items           : new Ext.grid.GridPanel({
         id      : 'chatGridPanel'
        ,cls     : 'chromeNoHorizontalScrollbar'
        ,store   : sto
        ,columns : [
           {id : 'description',dataIndex : 'description',header : 'Description'}
          ,{id : 'date',dataIndex : 'date',header : 'Date',width : 35,align : 'right',renderer : function(val,p,rec) {
            if (val > new Date(1970)) {
              return dateToFriendlyString(new Date(val.getTime() - val.getTimezoneOffset() * 60000)) + '<br>' + rec.get('user');
            }
          }}
          ,{id : 'title',dataIndex : 'title',header : 'Title'}
        ]
        ,border           : false
        ,hideHeaders      : true
        ,disableSelection : true
        ,loadMask         : true
        ,view             : new Ext.grid.GroupingView({
           forceFit          : true
          ,groupTextTpl      : '{text}'
          ,hideGroupedColumn : true
          ,showGroupName     : false
        })
      })
      ,tbar            : [
        {
           text    : 'Go back to view all conversations'
          ,tooltip : 'Return to the list of all conversations'
          ,icon    : 'img/back16.png'
          ,hidden  : true
          ,id      : 'conversationBackButton'
          ,handler : function() {
            var sto = Ext.getCmp('chatGridPanel').getStore();
            if (sto.baseParams) {
              delete sto.baseParams.getConversation;
            }
            sto.load();
          }
        }
        ,' '
        ,new Ext.form.Label({
           id   : 'conversationDirections'
          ,html : 'Please provide us with feedback in the public survey below. To respond privately, e-mail comments to info@maracoos.org.'
        })
        ,'->'
        ,{
          icon : 'img/blank.png'
        }
      ]
/*
      ,tbar            : [
        new Ext.form.TextField({
           emptyText  : 'Start a new conversation.'
          ,id         : 'chatWinNewConversationTextField'
          ,width      : 175
          ,listeners  : {specialkey : function(but,e) {
            if (e.keyCode == e.ENTER && Ext.getCmp('chatWinNewConversationTextField').getValue() != '') {
              if (!userId) {
                Ext.Msg.alert('Chat error',"We're sorry, but you must be logged in to post to the chat window.");
              }
              else {
                var sto = Ext.getCmp('chatGridPanel').getStore();
                sto.baseParams = {
                   user            : userId
                  ,newConversation : Ext.getCmp('chatWinNewConversationTextField').getValue()
                };
                sto.load();
              }
            }
          }}
        })
        ,{
           icon    : 'img/comments16.png'
          ,tooltip : 'Start a new conversation.'
          ,handler : function() {
            if (Ext.getCmp('chatWinNewConversationTextField').getValue() != '') {
              if (!userId) {
                Ext.Msg.alert('Chat error',"We're sorry, but you must be logged in to post to the chat window.");
              }
              else {
                var sto = Ext.getCmp('chatGridPanel').getStore();
                sto.baseParams = {
                   user            : userId
                  ,newConversation : Ext.getCmp('chatWinNewConversationTextField').getValue()
                };
                sto.load();
              }
            }
          }
        }
        ,' '
        ,' '
        ,' OR '
        ,' '
        ,' '
        ,' '
        ,new Ext.form.TextField({
           emptyText  : 'Search for keywords.'
          ,id         : 'chatWinSearchTextField'
          ,width      : 175
          ,listeners  : {specialkey : function(but,e) {
            if (e.keyCode == e.ENTER && Ext.getCmp('chatWinSearchTextField').getValue() != '') {
              var sto = Ext.getCmp('chatGridPanel').getStore();
              sto.baseParams = {
                search : Ext.getCmp('chatWinSearchTextField').getValue()
              };
              sto.load();
            }
          }}
        })
        ,{
           icon    : 'img/google_custom_search16.png'
          ,tooltip : 'Search conversations for keywords.'
          ,handler : function() {
            if (Ext.getCmp('chatWinSearchTextField').getValue() != '') {
              var sto = Ext.getCmp('chatGridPanel').getStore();
              sto.baseParams = {
                search : Ext.getCmp('chatWinSearchTextField').getValue()
              };
              sto.load();
            }
          }
        }
        ,'->'
        ,{
           text    : 'Start over'
          ,icon    : './js/ext-3.3.0/resources/images/default/grid/refresh.gif'
          ,tooltip : 'Clear everything and refresh conversations'
          ,handler : function() {
            var sto = Ext.getCmp('chatGridPanel').getStore();
            delete sto.baseParams;
            sto.load();
            Ext.getCmp('chatWinNewConversationTextField').reset();
            Ext.getCmp('chatWinSearchTextField').reset();
          }
        }
      ]
*/
/*
      ,bbar            : new Ext.PagingToolbar({
         pageSize    : chatLimitPerPage
        ,store       : sto
        ,id          : 'chatPagingToolbar'
        ,displayInfo : true
        ,displayMsg  : 'Displaying results {0} - {1} of {2}'
        ,emptyMsg    : 'No results to display'
      })
*/
    }).show();
  }
}

function moreInfo(title,name) {
  var tt = Ext.getCmp('moreInfoTooltip' + name);
  if (tt && tt.title != title) {
    tt.destroy();
    tt = false;
  }
  if (!tt) {
    tt = new Ext.ToolTip({
       id           : 'moreInfoTooltip' + name
      ,target       : 'moreInfo' + name
      ,title        : title
      ,html         : map.getLayersByName(name)[0].moreInfo
      ,anchor       : 'right'
      ,closable     : true
      ,autoHide     : false
    });
  }
  tt.show();
}

function pokeMap() {
  // the basemaps may get out of step before a print, so poke it
  if (map.getZoom() > 1) {
    map.zoomIn();
    map.zoomOut();
  }
  else {
    map.zoomOut();
    map.zoomIn();
  }
}

function wmsGetCaps(title,u) {
  Ext.getCmp('searchResultsGridPanel').getEl().mask('<table><tr><td>Loading...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');
  OpenLayers.Request.issue({
     url      : 'get.php?url=' + encodeURIComponent(u)
    ,callback : function(r) {
      Ext.getCmp('searchResultsGridPanel').getEl().unmask();
      var caps = new OpenLayers.Format.WMSCapabilities().read(r.responseText);
      if (!caps || !caps.capability) {
        Ext.Msg.alert('WMS exception','There was an error querying this data service.');
        return;
      }
      var layers = [];
      for (var i = 0; i < caps.capability.layers.length; i++) {
        var sTitles = [];
        var s       = {};
        for (var j = 0; j < caps.capability.layers[i].styles.length; j++) {
          sTitles.push(caps.capability.layers[i].styles[j].title);
          s[caps.capability.layers[i].styles[j].title] = [
             caps.capability.layers[i].styles[j].title
            ,caps.capability.layers[i].styles[j].name
          ];
        }
        sTitles.sort();
        var styles = [];
        for (var j = 0; j < sTitles.length; j++) {
          styles.push(s[sTitles[j]]);
        }
        var t = [];
        if (caps.capability.layers[i].dimensions && caps.capability.layers[i].dimensions['time'] && caps.capability.layers[i].dimensions['time'].values) {
          for (var j = 0; j < caps.capability.layers[i].dimensions['time'].values.length; j++) {
            t.push(caps.capability.layers[i].dimensions['time'].values[j].replace(/ |\n/g,''));
          }
        }
        t.reverse();
        var times = [];
        for (var j = 0; j < t.length; j++) {
          var p = t[j].split('/');
          if (p.length == 3) {
            var d0 = isoDateToDate(p[0]);
            var d1 = isoDateToDate(p[1]);
            if (p[2] == 'PT1H') {
              for (var k = d1.getTime(); k >= d0.getTime(); k -= 3600000) {
                times.push([
                   makeTimeParam(new Date(k))
                  ,makeTimeParam(new Date(k))
                ]);
              }
            }
            else {
              times.push([
                 makeTimeParam(d0)
                ,makeTimeParam(d1)
              ]);
            }
          }
          else {
            times.push([
               t[j]
              ,t[j]
            ]);
          }
        }
        layers.push([
           caps.capability.layers[i].title
          ,caps.capability.layers[i].name
          ,caps.capability.layers[i].llbbox
          ,caps.capability.request.getmap.get.href
          ,styles
          ,[
             ['Auto color range'   ,'auto']
            ,['Default color range','default']
            ,['0 - 0.1'            ,'0,0.1']
            ,['0 - 0.2'            ,'0,0.2']
            ,['0 - 0.5'            ,'0,0.5']
            ,['0 - 1'              ,'0,1']
            ,['0 - 5'              ,'0,5']
            ,['0 - 10'             ,'0,10']
            ,['0 - 20'             ,'0,20']
            ,['0 - 30'             ,'0,30']
            ,['0 - 50'             ,'0,50']
          ]
          ,times
        ]);
      }

      var id = Ext.id();
      var w = new Ext.Window({
         title     : title
        ,id        : 'win.' + id
        ,width     : 700
        ,height    : 480
        ,constrainHeader : true
        ,tbar      : [
          'Layer options: '
          ,new Ext.form.ComboBox({
            store          : new Ext.data.ArrayStore({
               fields : ['title','name','llbbox','getmapHref','styles','colorscalerange','time']
              ,data   : layers
            })
            ,id             : 'layers.' + id
            ,width          : 175
            ,forceSelection : true
            ,editable       : false
            ,triggerAction  : 'all'
            ,emptyText      : 'No options exist'
            ,cls            : Ext.isChrome ? 'chromeInput' : ''
            ,selectOnFocus  : true
            ,mode           : 'local'
            ,displayField   : 'title'
            ,valueField     : 'name'
            ,value          : layers.length > 0 ? layers[0][1] : null
            ,listeners      : {select : function(cb,rec,i) {
              var oldParams = {};
              var lyr = previewMaps[id].getLayersByName('layer.' + id)[0];
              if (lyr) {
                oldParams = lyr.params;
                previewMaps[id].removeLayer(lyr);
              }
              var lyr = new OpenLayers.Layer.WMS(
                 'layer.' + id
                ,rec.get('getmapHref')
                ,{
                   layers          : rec.get('name')
                  ,transparent     : true
                  ,styles          : getBestWmsParam(rec.get('styles'),oldParams['STYLES'],'rainbow')
                  ,colorscalerange : getBestWmsParam(rec.get('colorscalerange'),oldParams['COLORSCALERANGE'],'rainbow')
                  ,time            : getBestWmsParam(rec.get('time'),oldParams['TIME'])
                }
                ,{
                   isBaseLayer      : false
                  ,transitionEffect : 'resize'
                  ,projection       : proj3857
                  ,singleTile       : true
                }
              );
              lyr.events.register('loadstart',this,function(e) {
                Ext.getCmp('win.' + e.object.name.split('.')[1]).getEl().mask('<table><tr><td>Updating...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');
              });
              lyr.events.register('loadend',this,function(e) {
                Ext.getCmp('win.' + e.object.name.split('.')[1]).getEl().unmask();
              });
              previewMaps[id].addLayer(lyr);

              var u = lyr.url + (lyr.url.indexOf('?') >= 0 ? '' : '?') + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic'
                + '&COLORSCALERANGE=' + lyr.params['COLORSCALERANGE']
                + '&LAYER=' + lyr.params['LAYERS']
                + '&PALETTE=' + lyr.params['STYLES'].split('/')[1];
              if (lyr.params['COLORSCALERANGE'] == 'auto') {
                u = 'img/noLegend.png';
              }
              document.getElementById('legend.' + id).src = u;

              Ext.getCmp('styles.' + id).getStore().loadData(rec.get('styles'));
              rec.get('styles').length > 0 ? Ext.getCmp('styles.' + id).setValue(getBestWmsParam(rec.get('styles'),oldParams['STYLES'],'rainbow')) : null;
              Ext.getCmp('colorscalerange.' + id).getStore().loadData(rec.get('colorscalerange'));
              rec.get('colorscalerange').length > 0 ? Ext.getCmp('colorscalerange.' + id).setValue(getBestWmsParam(rec.get('colorscalerange'),oldParams['COLORSCALERANGE']),'rainbow') : null;
              var d = [];
              for (var i = 0; i < rec.get('time').length; i++) {
                d.push(isoDateToDate(rec.get('time')[i][0]).format('UTC:mm/dd/yyyy'));
              }
              // all times we recieved should be negated for disabled dates
              Ext.getCmp('time.' + id).disabledDatesRE = new RegExp('^(?!.*' + d.join('|').replace(/\//g,'\\/') + ').*$');
              var t0 = getBestWmsParam(rec.get('time'),oldParams['TIME']);
              if (t0 && t0 != '') {
                t0 = isoDateToDate(t0).format('UTC:mm/dd/yyyy');
              } 
              rec.get('time').length > 0 ? Ext.getCmp('time.' + id).setValue(t0) : null;
              Ext.getCmp('time.' + id).store = new Ext.data.ArrayStore({
                 fields : ['title','name']
                ,data   : rec.get('time')
              });
              // find the 1st complete TIME that matches the picker's DAY
              Ext.getCmp('time.' + id).addListener('valid',function(f) {
                var idx = f.store.findBy(function(rec,id) {
                  var recP = rec.get('title').split(/-|T/);
                  return (f.getValue().format('UTC:yyyy-mm-dd') == recP[0] + '-' + recP[1] + '-' + recP[2]);
                });
                previewMaps[id].getLayersByName('layer.' + id)[0].mergeNewParams({TIME : Ext.getCmp('time.' + id).store.getAt(idx).get('title')});
              });
            }}
          })
          ,' '
          ,new Ext.form.ComboBox({
            store          : new Ext.data.ArrayStore({
              fields : ['title','name'] 
            })
            ,id             : 'styles.' + id
            ,width          : 100
            ,forceSelection : true
            ,editable       : false
            ,triggerAction  : 'all'
            ,emptyText      : 'No options exist'
            ,cls            : Ext.isChrome ? 'chromeInput' : ''
            ,selectOnFocus  : true
            ,mode           : 'local'
            ,displayField   : 'title'
            ,valueField     : 'name'
            ,listeners      : {select : function(cb,rec,i) {
              previewMaps[id].getLayersByName('layer.' + id)[0].mergeNewParams({STYLES : rec.get('name')});
            }}
          })
          ,' '
          ,new Ext.form.ComboBox({
            store          : new Ext.data.ArrayStore({
              fields : ['title','name']
            })
            ,id             : 'colorscalerange.' + id
            ,width          : 125
            ,forceSelection : true
            ,editable       : false
            ,triggerAction  : 'all'
            ,emptyText      : 'No options exist'
            ,cls            : Ext.isChrome ? 'chromeInput' : ''
            ,selectOnFocus  : true
            ,mode           : 'local'
            ,displayField   : 'title'
            ,valueField     : 'name'
            ,listeners      : {select : function(cb,rec,i) {
              previewMaps[id].getLayersByName('layer.' + id)[0].mergeNewParams({COLORSCALERANGE : rec.get('name')});
            }}
          })
          ,'->'
          ,'Available date(s): '
          ,new Ext.form.DateField({
             id : 'time.' + id
          })
        ]
        ,html      : '<div style="width:700px;height:480px" id="map.' + id + '"><div class="previewMapLegend"><img id="legend.' + id + '" src="img/blank.png"></div></div>'
        ,listeners : {afterrender : function(w) {
          previewMaps[id] = new OpenLayers.Map('map.' + id,{
            layers            : [
              new OpenLayers.Layer.XYZ(
                 'ESRI Ocean'
                ,'http://services.arcgisonline.com/ArcGIS/rest/services/Ocean_Basemap/MapServer/tile/${z}/${y}/${x}.jpg'
                ,{
                   sphericalMercator : true
                  ,isBaseLayer       : true
                  ,wrapDateLine      : true
                }
              )
            ]
            ,projection        : proj900913
            ,displayProjection : proj4326
            ,units             : 'm'
            ,maxExtent         : new OpenLayers.Bounds(-20037508,-20037508,20037508,20037508.34)
            ,controls          : [new OpenLayers.Control.Zoom(),new OpenLayers.Control.Attribution()]
          });

          var navToolbarControl = new OpenLayers.Control.NavToolbar();
          previewMaps[id].addControl(navToolbarControl);
          navToolbarControl.controls[0].disableZoomBox();

          previewMaps[id].events.register('moveend',this,function() {
            if (navToolbarControl.controls[1].active) {
              navToolbarControl.controls[1].deactivate();
              navToolbarControl.controls[0].activate();
              navToolbarControl.draw();
            }
          });

          previewMaps[id].events.register('changelayer',this,function(e) {
            var u = e.layer.url + (e.layer.url.indexOf('?') >= 0 ? '' : '?') + '&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetLegendGraphic'
              + '&COLORSCALERANGE=' + e.layer.params['COLORSCALERANGE']
              + '&LAYER=' + e.layer.params['LAYERS']
              + '&PALETTE=' + e.layer.params['STYLES'].split('/')[1];
            if (e.layer.params['COLORSCALERANGE'] == 'auto') {
              u = 'img/noLegend.png';
            }
            document.getElementById('legend.' + id).src = u;
          });

          previewMaps[id].setCenter(new OpenLayers.LonLat(startupCenter[0],startupCenter[1]).transform(proj4326,proj900913),startupZoom);

          w.addListener('bodyresize',function(p,w,h) {
            var id = p.id.split('.')[1];
            var el = document.getElementById('map.' + id);
            if (el) {
              el.style.width  = w;
              el.style.height = h;
              previewMaps[id].updateSize();
            }
          });

          Ext.getCmp('layers.' + id).fireEvent('select',Ext.getCmp('layers.' + id),Ext.getCmp('layers.' + id).getStore().getAt(0));
        }}
      });
      w.show();
    }
  });
}

function sosGetObs(title,name,u,bbox,provider,id,minT,maxT) {
  Ext.getCmp('searchResultsGridPanel').getEl().mask('<table><tr><td>Loading...&nbsp;</td><td><img src="js/ext-3.3.0/resources/images/default/grid/loading.gif"></td></tr></table>','mask');

  OpenLayers.Request.issue({
     url      : 'getSearchObs.php'
    ,method   : 'POST'
    ,headers  : {'Content-Type' : 'application/x-www-form-urlencoded'}
    ,data     : OpenLayers.Util.getParameterString({
       description  : title
      ,getObs       : Ext.encode([u])
      ,location     : Ext.encode(bbox)
      ,provider     : provider
      ,organization : ''
      ,siteType     : ''
      ,url          : ''
      ,minT         : minT
      ,maxT         : maxT
    })
    ,callback : function(r) {
      Ext.getCmp('searchResultsGridPanel').getEl().unmask();
      var json = new OpenLayers.Format.JSON().read(r.responseText);
      var geojson = new OpenLayers.Format.GeoJSON();
      var f       = geojson.read(json[0])[0];
      f.geometry.transform(proj4326,proj900913);

      var a = preparePopup(
         f
        ,false // show everything
        ,false // not drawing graph from topObs
        ,false // not drawing graph from topObs
        ,false // not drawing graph from topObs
        ,true  // from search
        ,u.indexOf('herokuapp') >= 0
      );

      if (selectPopup && selectPopup.isVisible()) {
        selectPopup.hide();
      }
      selectPopup = new Ext.Window({
         items        : {border : false,html : a.html}
        ,title        : a.title.substr(0,40) + (a.title.length > 40 ? '...' : '')
        ,resizable    : false
        ,anchor       : 'bottom'
        ,target       : document.getElementById(id)
        ,constrainHeader : true
        ,listeners    : {hide : function(tt) {
          if (typeof e != 'undefined') {
            selectControl.unselect(e.feature);
          }
          if (!tt.isDestroyed && !Ext.isIE) {
            tt.destroy();
          }
        }}
        ,fromSearch   : OpenLayers.Util.getParameterString({
           description  : title
          ,getObs       : Ext.encode([u])
          ,location     : Ext.encode(bbox)
          ,provider     : provider
          ,organization : ''
          ,siteType     : ''
          ,url          : ''
          ,minT         : minT
          ,maxT         : (a.t ? makeTimeParam(a.t) + 'Z' : maxT)
        })
       });
      selectPopup.show();
    }
  });
}

function getBestWmsParam(newParams,oldParam,defaultSubstr) {
  if (!newParams.length) {
    return '';
  }
  for (var i = 0; i < newParams.length; i++) {
    if (oldParam && newParams[i][1] == oldParam) {
      return newParams[i][1];
    }
  }
  if (defaultSubstr) {
    for (var i = 0; i < newParams.length; i++) {
      if (newParams[i][1].indexOf(defaultSubstr) >= 0) {
        return newParams[i][1];
      }
    }
  }
  return newParams[0][1];
}

function addComment(id,conversation) {
  Ext.MessageBox.show({
     title     : 'Comment on this conversation'
    ,msg       : 'Enter your comment:'
    ,width     : 300
    ,buttons   : Ext.MessageBox.OKCANCEL
    ,multiline : true
    ,fn        : function(btn,text,cfg) {
      if (btn == 'ok' && text == '') {
        Ext.MessageBox.show(Ext.apply({},{msg : cfg.msg},cfg));
      }
      else if (btn == 'ok') {
        var sto = Ext.getCmp('chatGridPanel').getStore();
        sto.baseParams = {
           user       : userId
          ,newComment : id
          ,text       : text
        };
        if (typeof(conversation) == 'number') {
          sto.setBaseParam('getConversation',conversation);
        }
        sto.load();
      }
    }
  });
}

function viewConversation(id) {
  startChat(id);
}

function goSession() {
  Ext.MessageBox.show({
     title     : 'Login'
    ,msg       : 'Provide your email address to set or restore your browsing session:'
    ,width     : 300
    ,buttons   : Ext.MessageBox.OKCANCEL
    ,prompt    : true
    ,fn        : function(btn,text,cfg) {
      if (btn == 'ok' && text == '') {
        Ext.MessageBox.show(Ext.apply({},{msg : cfg.msg},cfg));
      }
      else if (btn == 'ok') {
        OpenLayers.Request.issue({
           url      : 'session.php?userId=' + encodeURIComponent(text)
          ,async    : false
          ,callback : function(r) {
            var json = new OpenLayers.Format.JSON().read(r.responseText);
            if (new RegExp(/^$|duplicate key/).test(json.err)) {
              userId = json.userId;
              var msgPermalink = json.permalink ? ' Click Yes to restore your last session, or click No to continue with your current session.' : '';
              var msgDuplicate = new RegExp(/duplicate key/).test(json.err) ? ' (If you thought you were creating a new account, then this email address is already taken, and you will need to select another one.)' : '';
              Ext.MessageBox.show({
                 title     : 'Welcome'
                ,msg       : 'Welcome, ' + json.userId + '.' + msgPermalink + msgDuplicate
                ,width     : 300
                ,buttons   : json.permalink ? Ext.MessageBox.YESNO :  Ext.MessageBox.OK
                ,fn        : function(btn) {
                  if (btn == 'yes') {
                    document.location = json.permalink + '&userId=' + encodeURIComponent(json.userId);
                  }
                }
              });
            }
            else {
              userId = false;
              Ext.Msg.alert('Error','There was a problem with this email address.  Please try again.',function() {
                Ext.MessageBox.show(Ext.apply({},{msg : cfg.msg},cfg));
              });
            }
          }
        });
      }
    }
  });
}

function convertDMS (coordinate, type, spaceOnly) {
  var coords = new Array();

  abscoordinate = Math.abs(coordinate)
  coordinatedegrees = Math.floor(abscoordinate);

  coordinateminutes = (abscoordinate - coordinatedegrees)/(1/60);
  tempcoordinateminutes = coordinateminutes;
  coordinateminutes = Math.floor(coordinateminutes);
  coordinateseconds = (tempcoordinateminutes - coordinateminutes)/(1/60);
  coordinateseconds =  Math.round(coordinateseconds*10000);
  coordinateseconds /= 10000;

  if( coordinatedegrees < 10 )
    coordinatedegrees = "0" + coordinatedegrees;

  if( coordinateminutes < 10 )
    coordinateminutes = "0" + coordinateminutes;

  if( coordinateseconds < 10 ) {
    coordinateseconds = "0" + coordinateseconds.toFixed(3);
  }
  else {
    coordinateseconds = coordinateseconds.toFixed(3);
  }

  if (spaceOnly) {
    var factor = 1;
    if (coordinate < 0) {
      factor = -1;
    }
    return factor * coordinatedegrees + ' ' + coordinateminutes + ' ' + coordinateseconds + ' ';
  }
  else {
    return coordinatedegrees + '&deg; ' + coordinateminutes + "' " + coordinateseconds + '" ' + this.getHemi(coordinate, type);
  }
}

/**
 * Return the hemisphere abbreviation for this coordinate.
 */
function getHemi(coordinate, type) {
  var coordinatehemi = "";
  if (type == 'LAT') {
    if (coordinate >= 0) {
      coordinatehemi = "N";
    }
    else {
      coordinatehemi = "S";
    }
  }
  else if (type == 'LON') {
    if (coordinate >= 0) {
      coordinatehemi = "E";
    } else {
      coordinatehemi = "W";
    }
  }

  return coordinatehemi;
}

function showSplash(fromButton) {
  if (!splashHtml) {
    Ext.Msg.alert('Help unavailable',"We're sorry, but help is currently unavailable.");
    return;
  }
  var c = Ext.getCmp('splahWin');
  if (!c) {
    new Ext.Window({
       width           : 515
      ,minWidth        : 515
      ,autoHeight      : true
      ,stateful        : false
      ,title           : splashTitle
      ,id              : 'splashWin'
      ,bodyStyle       : 'background:white'
      ,constrainHeader : true
      ,modal           : true
      ,html            : '<div id="splash">' + splashHtml + '</div>'
      ,bbar            : [
         {text : ''}
        ,'->'
        ,'Hide this introduction on my next visit from this computer.'
        ,' '
        ,' '
        ,new Ext.form.Checkbox({
           id      : 'hideSplashOnStartupCheckbox'
          ,checked : cp.get('hideSplashOnStartupCheckbox')
        })
      ]
      ,listeners       : {close : function(w) {
        cp.set('hideSplashOnStartupCheckbox',Ext.getCmp('hideSplashOnStartupCheckbox').checked);
      }}
    }).show();
  }
}

function syncWatermark() {
  var lyr = map.getLayersByName('watermark')[0];
  lyr.removeFeatures(lyr.features);
  var center = map.getCenter();
  var f = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(center.lon,center.lat));
  var label = '';
  byCatchMapsStore.each(function(rec) {
    var legends = rec.get('wmsLegends');
    for (var i = 0; i < legends.length; i++) {
      if (map.getLayersByName(legends[i])[0].visibility) {
        label = 'Last bycatch report\n' + rec.get('showLegendTitle')[i].split(/Updated |<br>/).pop();
      }
    }
  });
  // special case for ocean fronts
  weatherMapsStore.each(function(rec) {
    var legends = rec.get('wmsLegends');
    for (var i = 0; i < legends.length; i++) {
      if (legends[i] == 'Ocean fronts' && map.getLayersByName(legends[i])[0].visibility) {
        label += (label != '' ? '\n' : '') + 'Ocean Fronts\ncurrently under development';
      }
    }
  });
  f.attributes.label = label;
  lyr.addFeatures([f]);
  lyr.redraw();
}

function goTheme(s) {
  Ext.getCmp('browseOceanConditionDataWindow').setHeight(340);
  var a = ['Satellite','Models','Buoys'];
  for (var i = 0; i < a.length; i++) {
    s == a[i] ? Ext.getCmp('fieldSet' + a[i]).show() : Ext.getCmp('fieldSet' + a[i]).hide();
    Ext.getCmp('theme' + a[i]).toggle(s == a[i],true);
    var el = document.getElementById('theme' + a[i] + 'Title');
    if (el) {
      if (s == a[i]) {
        el.style.fontWeight = 'bold';
        el.style.color      = '#15428b';
      }
      else {
        el.style.fontWeight = '';
        el.style.color      = '';
      }
    }
  }

  if (s == 'Buoys') {
    Ext.getCmp('findBuoySpacer').show();
    Ext.getCmp('findBuoyControl').show();
  }
  else {
    Ext.getCmp('findBuoySpacer').hide();
    Ext.getCmp('findBuoyControl').hide();
    Ext.getCmp('bbarOceanConditionBbarPanel').show();
  }

  if (s == 'Satellite' || s == 'Models') {
    Ext.getCmp('contrastHeader').show();
    Ext.getCmp('contrastSliderWrapper').show();
  }
  else {
    Ext.getCmp('contrastHeader').hide();
    Ext.getCmp('contrastSliderWrapper').hide();
  }

  if (s == 'Models') {
    Ext.getCmp('timeSpacer').show();
    Ext.getCmp('timeControl').show();
  }
  else {
    Ext.getCmp('timeSpacer').hide();
    Ext.getCmp('timeControl').hide();
  }

  if (s == 'Buoys') {
    changeMode('observations');
    for (var i in activeObs) {
      if (activeObs[i]) {
        goObs(i);
      }
    }
  }
  else if (s == 'Satellite') {
    changeMode('weather');
  }
  else if (s == 'Models') {
    changeMode('forecasts');
  }
}

function goObs(s,f) {
  var a = ['winds','waves','waterTemp','waterLevel','dissolvedOxygen','airTemperature','all','none'];
  for (var i = 0; i < a.length; i++) {
    Ext.getCmp('obs' + a[i]).toggle(s == a[i],true);
    var el = document.getElementById('obs' + a[i] + 'Title');
    if (el) {
      if (s == a[i]) {
        el.style.fontWeight = 'bold';
        el.style.color      = '#15428b';
      }
      else {
        el.style.fontWeight = '';
        el.style.color      = '';
      }
    }
  }
  selectWeatherStationType(s,f);
}

function goSatellite(s) {
  var a = ['Chlorophyll concentration','Weather RADAR and cloud imagery','Ocean fronts','Satellite water temperature','None'];
  for (var i = 0; i < a.length; i++) {
    Ext.getCmp('satellite' + a[i]).toggle(s == a[i],true);
    var el = document.getElementById('satellite' + a[i] + 'Title');
    if (el) {
      if (s == a[i]) {
        el.style.fontWeight = 'bold';
        el.style.color      = '#15428b';
      }
      else {
        el.style.fontWeight = '';
        el.style.color      = '';
      }
    }
  }
  var combo = Ext.getCmp('weatherMapsTypeComboBox');
  combo.setValue(s);
  combo.fireEvent('select',combo,combo.getStore().getAt(combo.getStore().findExact('id',s)));
}

function goModel(s) {
  var a = ['Winds','Waves','Surface water temperature','Currents (global)','Currents (regional)','Bottom water temperature','Currents (New York Harbor)','None'];
  for (var i = 0; i < a.length; i++) {
    Ext.getCmp('model' + a[i]).toggle(s == a[i],true);
    var el = document.getElementById('model' + a[i] + 'Title');
    if (el) {
      if (s == a[i]) {
        el.style.fontWeight = 'bold';
        el.style.color      = '#15428b';
      }
      else {
        el.style.fontWeight = '';
        el.style.color      = '';
      }
    }
  }
  var combo = Ext.getCmp('forecastMapsTypeComboBox');
  combo.setValue(s);
  combo.fireEvent('select',combo,combo.getStore().getAt(combo.getStore().findExact('id',s)));
}

function goByCatch(s) {
  var a = ['Bottom trawl Northeast/MA','Bottom trawl Rhode Island','Mid-water trawl Area 2','Mid-water trawl Cape Cod','Closed area 1 Georges Bank','Closed area 2 Georges Bank','Nantucket Lightship','None'];
  for (var i = 0; i < a.length; i++) {
    Ext.getCmp('byCatch' + a[i]).toggle(s == a[i],true);
    var el = document.getElementById('byCatch' + a[i] + 'Title');
    if (el) {
      if (s == a[i]) {
        el.style.fontWeight = 'bold';
        el.style.color      = '#15428b';
      }
      else {
        el.style.fontWeight = '';
        el.style.color      = '';
      }
    }
  }
  var combo = Ext.getCmp('byCatchMapsTypeComboBox');
  combo.setValue(s);
  map.zoomToByCatch = true;
  combo.fireEvent('select',combo,combo.getStore().getAt(combo.getStore().findExact('id',s)));
  map.zoomToByCatch = false;

  document.getElementById('byCatchLegend').style.visibility = s != 'None' ? 'visible' : 'hidden';
}
