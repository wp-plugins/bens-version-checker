version_checker = {};

(function( $ ) {
	'use strict';
$(function(){
	version_checker.Website = Backbone.Model.extend({
		initialize: function(){
			// Fire the modelchanged event to check if all sites are loaded
			this.on("change", function() { version_checker.mainView.modelChanged(); });
		},

		defaults: function() {
			// Return the starting values when someone loads the application
			return {
				type: "Unknown",
				version: "?",
				loaded: true,
				styles: "unknown"
			}
		},

		reload: function(){
			var obj = this;

			// Disable the refresh button
			$("#refresh").attr("disabled", true);

			// Update the model to show it is loading
			this.set({type: "Waiting...", styles: "waiting", version: "?", loaded: false});

			// Load the php script to check this site
			var xmlhttp = new XMLHttpRequest();

			xmlhttp.onreadystatechange = function() {
				// Check if this request has begun
				if(xmlhttp.readyState == 2)
					obj.set({type: "Loading", styles: "loading", version: "?", loaded: false});
			};

			xmlhttp.onloadend = function() {
				// Push response to this object
				obj.set($.parseJSON(xmlhttp.responseText) || {version: "Error with plugin AJAX", styles: "other", type: "Error"});
				obj.set("loaded", true);
				version_checker.mainView.modelChanged();
			};

			// Send request
			xmlhttp.open("POST", ajaxurl);
			xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xmlhttp.send("action=lookup_site&site=" + this.get("site"));
		}
	});

	var Websites = Backbone.Collection.extend({
		model: version_checker.Website,
		compare: "site",
		flip: false,

		comparator: function(site){
			//Compare the columns
			var str = site.get(this.compare);

			str = str.toLowerCase();
			str = str.split("");

			//Reverse sort order if flag is set
			if(this.flip)
				str = _.map(str, function(letter) { return String.fromCharCode(-(letter.charCodeAt(0))) });

			return str;
		},

		loaded: function(){
			//Check if all sites are loaded
			return this.where({loaded: false}).length == 0;
		}
	});

	version_checker.TableRow = Backbone.View.extend({
		tagName: "tr",
		className: "",

		template: _.template($("#website-row").html()),

		initialize: function() {
			//Render whenever the model is changed
			this.listenTo(this.model, "change", this.render);
			this.render();
		},

		render: function() {
			//Renders our row
			this.$el.removeClass();
			this.$el.addClass(this.model.get("styles"));
			this.$el.html(this.template(this.model.toJSON()));

			var me = this;

			this.$el.find(".refresh").click( function() { me.model.reload(); } );

			return this;
		}
	});

	version_checker.MainView = Backbone.View.extend({
		el: $("#resultList"),
		tagName : 'tbody',

		initialize: function() {
			var me = this;
			//Create and load sites list
			me.sites = new Websites();

			//Listeners
			$(".sort").click(function() {
				me.sortBy(this.getAttribute("by"), false);
			});

			$("#refresh").click(function() {
				//Make sure we don't send duplicate requests
				if(!$("#refresh").attr("disabled")){
					me.reloadAll();
				}
			});
		},

		render: function(){
			var me = version_checker.mainView;

			this.$el.empty();
			this.sites.sort();

			this.sites.forEach(function(model){
				//Render each row
				var row = new version_checker.TableRow({ model: model }).render();
				me.$el.append(row.el);
			});
		},

		add: function(model){
			this.sites.add(model);
		},

		clearAll: function() {
			var model;

			//Delete all sites
			while (model = this.sites.first()) {
				model.destroy();
			}

			this.render();
		},

		loadAll: function(){
			var me = version_checker.mainView;

			//Clear our current collection
			this.clearAll();

			//Load in the new json
			var data = $.parseJSON($("#version_checker_site_json").val());
			_.each(data, function(site) {
				me.add(new version_checker.Website({site: site}));
			});

			//Fire the modelchanged function to update the check button
			this.modelChanged();
			this.render();
		},

		reloadAll: function(){
			var me = version_checker.mainView;

			this.sites.forEach(function(site){
				//Reload each site
				site.reload();
			});

			me.render();
		},

		sortBy: function(column, reverse){
			//Logic for sorting
			if(reverse){
				this.sites.flip = true;
				this.sites.compare = column;
			} else {
				if(this.sites.compare != column){
					this.sites.compare = column;
					this.sites.flip = true;
				} else {
					this.sites.flip = !this.sites.flip;
				}
			}

			this.render();
		},

		modelChanged: function(){
			//Check if all sites are loaded
			if(this.sites.loaded()) {
				//If they are, enable the refresh button
				$("#refresh").attr("disabled", false);
			}
		}
	});

	version_checker.mainView = new version_checker.MainView();
	version_checker.mainView.loadAll();
});
})( jQuery );
