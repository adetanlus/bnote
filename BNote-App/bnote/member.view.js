sap.ui.jsview("bnote.member", {
	
	getControllerName: function() {
		return "bnote.member";
	},
	 
	createContent: function(){
		var memberList = new sap.m.List({
            headerText: "Mitspieler",
        });
		
        memberList.bindItems({
        	growingScrollToLoad : "true",
            path : "/contacts",
            sorter : new sap.ui.model.Sorter("name"),
            template : new sap.m.StandardListItem({
                title: "{fullname}",
                icon: "icons/proben.png",
                description: "{mobile}",
                type: sap.m.ListType.Navigation,
                press: function(evt) {
                	  var oBindingContext = evt.getSource().getBindingContext(); // evt.getSource() is the ListItem
                      memberdetailView.setBindingContext(oBindingContext); // make sure the detail page has the correct data context
                      
                                          
                      app.to("memberdetail");
                }
            })
        });
	
        jQuery.sap.require("sap.ui.core.IconPool");
        var memberBar = new sap.m.OverflowToolbar({
      	  active: true,
      	  design: sap.m.ToolbarDesign.Solid,
      	  content: [
      		   new sap.m.Button({
      			   text: "Start",
      			   icon: sap.ui.core.IconPool.getIconURI( "home" ),
      			   press: function(){
      				   app.to("start")
      			   }
      		   }),
      		   new sap.m.Button({
      			   text: "Mitspieler",
      			   icon: sap.ui.core.IconPool.getIconURI( "person-placeholder" ),
      			   press: function(){
      				   app.to("member")
      			   }
      		   }),
      		   new sap.m.Button({
      			   text: "Kommunikation",
      			   icon: sap.ui.core.IconPool.getIconURI( "email" ),
      			   press: function(){
      				   app.to("communication")
      			   }
      		   })
      		   ]
        });
	
	var page = new sap.m.Page("MemberPage", {
        title: "Mitspieler",
        showNavButton: true,
        navButtonPress: function() {
            app.back();
        }, 
		content: [ memberList ],
        footer: [ memberBar ]
	});
	return page;
	}
});