$(function()
{
	// the widget definition, where "itop" is the namespace,
	// "newsroom_menu" the widget name
	$.widget( "itop.newsroom_menu",
	{
		// default options
		options:
		{
			image_icon: '',
			cache_uuid: '',
			display_limit: 7,
			placeholder_image_icon: '',
			providers: [],
			labels: {
				'no_message': 'No new message',
				'mark_all_as_read': 'Mark all as read',
				'view_all': 'View all messages'
			}
		},
	
		// the constructor
		_create: function()
		{
			var me = this;
			this.aMessageByProvider = [];
			$(me.element).popover_menu({'toggler': '[data-role="ibo-navigation-menu--notifications-toggler"]'});
			$('[data-role="ibo-navigation-menu--notifications-toggler"]').on('click', function(oEvent) {
				var oEventTarget = $(oEvent.target);
				var aEventTargetPos = oEventTarget.position();

				$(me.element).css({
					'top': (aEventTargetPos.top + parseInt(oEventTarget.css('marginTop'), 10) -  $(me.element).height()) + 'px',
					'left': (aEventTargetPos.left + parseInt(oEventTarget.css('marginLeft'), 10) + oEventTarget.width()) + 'px',
					'max-height' : (aEventTargetPos.top + parseInt(oEventTarget.css('marginTop'), 10) - 100) + 'px'
				});
				$(me.element).popover_menu("openPopup");
			});
			this.element
			.addClass('itop-newsroom_menu');
			
			this._load();
		},
	
		// called when created, and later when changing options
		_refresh: function()
		{
		},
		// events bound via _bind are removed automatically
		// revert other modifications here
		_destroy: function()
		{
			this.element
			.removeClass('itop-newsroom_menu');
		},
		// _setOptions is called with a hash of all options that are changing
		_setOptions: function()
		{
			this._superApply(arguments);
		},
		// _setOption is called for each individual option that is changing
		_setOption: function(key, value)
		{
			if (this.options[key] != value)
			{
				// If any option changes, clear the cache BEFORE applying the new settings
				this._clearCache();
			}
			
			this._superApply(arguments);
		},
		_load: function()
		{
			var me = this;
			setTimeout(function() { me._getAllMessages(); }, 1000);
		},
		_getAllMessages: function()
		{
			this.aMessageByProvider = [];
			this._getMessages(0); // start at the first provider (index == 0)
		},
		_getMessages: function(idxProvider)
		{
			var sKey = this._makeCacheKey(idxProvider);
			var oJSONData = this._getCachedData(idxProvider);
			if (oJSONData != null)
			{
				this._onMessagesFetched(idxProvider, oJSONData);
			}
			else
			{
				this._fetchMessages(idxProvider); // Asynchronous
			}
		},
		_fetchMessages: function(idxProvider)
		{
			var sUrl = this.options.providers[idxProvider].fetch_url;
			var me = this;
			var idx = idxProvider;
			
			$.ajax({ type: "GET",
		        	 url: sUrl,
		        	 async: true,
		        	 dataType : 'jsonp',
		        	 crossDomain: true,
		        	 jsonp: "callback"
		     })
		     .done(function(oJSONData) {
		    	 me._cacheData(idx, oJSONData);
		    	 me._onMessagesFetched(idx, oJSONData);
		    }).error(function() {
		    	 console.warn('Newsroom: failed to fetch data from the web for provider '+idx+' url: '+me.options.providers[idxProvider].fetch_url);
		    	 me._cacheData(idx, []);
		    	 me._onMessagesFetched(idx, []);		    	
		    });
		},
		_onMessagesFetched: function(idxProvider, oJSONData)
		{
			this.aMessageByProvider[idxProvider] = oJSONData;
			if ((1+idxProvider) < this.options.providers.length)
			{
				this._getMessages(idxProvider+1); // Process the next provider
			}
			else
			{
				this._onAllMessagesFetched(); // All messages retrieved
			}
		},
		_onAllMessagesFetched: function()
		{
			var aAllMessages = [];
			for(var k in this.aMessageByProvider)
			{ 
				for(var j in this.aMessageByProvider[k])
				{
					var oMsg = this.aMessageByProvider[k][j];
					oMsg.id = ''+oMsg.id; // Stringify
					
					// Process the provider specific placeholders, if any
					if (this.options.providers[k].placeholders !== undefined)
					{
						for(var sSearch in this.options.providers[k].placeholders)
						{
							var sReplace = this.options.providers[k].placeholders[sSearch];
							var sResult = oMsg.url.replace(sSearch, sReplace);
							oMsg.url = sResult;
						}
					}
					oMsg.provider = k;
					aAllMessages.push(oMsg);
				}
			}
			
			aAllMessages.sort(function(msg1, msg2) {
				if (msg1.priority < msg2.priority) return -1;
				if (msg1.priority > msg2.priority) return 1;
				var oDate1 = new Date(msg1.start_date);
				var oDate2 = new Date(msg2.start_date);
				if (oDate1 > oDate2) return -1;
				if (oDate1 < oDate2) return 1;
				return 1;
			});
			
			this._buildMenu(aAllMessages);
		},
		_buildDismissAllSection: function()
		{
			return '<div class="ibo-popover-menu--section ibo-navigation-menu--notifications-dismiss-all" data-role="ibo-popover-menu--section"><a class="ibo-popover-menu--item" data-role="ibo-navigation-menu--notifications-dismiss-all" ><i class="fas fa-fw fa-check ibo-navigation-menu--notifications-dismiss-all--icon"></i>'+this.options.labels.mark_all_as_read+'</a> <hr class="ibo-popover-menu--item ibo-popover-menu--separator"> </div>';
		},
		_buildMessageSection: function()
		{
			return '<div class="ibo-popover-menu--section" data-role="ibo-popover-menu--section">';
		},
		_buildShowAllMessagesSection: function()
		{
			return '<div class="ibo-popover-menu--section ibo-navigation-menu--notifications--messages-section" data-role="ibo-popover-menu--section">';
		},
		_buildMessageItems: function(sId, sText, sImage, sStartDate, sProvider, sUrl)
		{
			var sNewMessageIndicator = '<div class="ibo-navigation-menu--notifications--item--new-message-indicator"></div>';
			sImage = '<img class="ibo-navigation-menu--notifications--item--image" src="'+sImage+'"><i class="ibo-navigation-menu--notifications--item--image '+this.options.placeholder_image_icon+'"></i>';

			var div = document.createElement("div");
			div.textContent = sText;
			var sDescription = div.innerHTML; // Escape HTML entities for XSS prevention
			//Todo: make only one converter per loop
			var converter = new showdown.Converter({noHeaderId: true});
			
			var sRichDescription = '<div class="ibo-navigation-menu--notifications--item--content">' + converter.makeHtml(sDescription) +'</div>';
			
			var sBottomText = '<span class="ibo-navigation-menu--notifications--item--bottom-text">'+ sImage + '<span>' + this.options.providers[sProvider].label+'</span> <span> ' + moment(sStartDate).fromNow()+'</span></span>';
			
			return '<a class="ibo-popover-menu--item ibo-navigation-menu--notifications-item" data-role="ibo-navigation-menu--notifications-item" data-msg-id="'+sId+'" data-provider-id="'+sProvider+'" href="'+sUrl+'" target="_blank" id="newsroom_menu_item_'+sId+'">' +
				sNewMessageIndicator + sRichDescription + sBottomText +'</a>';
		},
		_buildNoMessageItem: function()
		{
			return '<div class="ibo-popover-menu--item ibo-popover-menu--item--no-message">' + this.options.labels.no_message + '<img class="ibo-popover-menu--item--no-message--image" src="../images/illustrations/undraw_empty.svg" alt="TODO"/></div>';
		},
		_buildSingleShowAllMessagesItem: function()
		{
			return '<a class="ibo-popover-menu--item" data-role="ibo-navigation-menu--notifications-show-all" href="'+me.options.providers[0].view_all_url+'">' + this.options.labels.view_all + '</a>';
		},
		_buildMultipleShowAllMessagesItem: function(aUnreadMessagesByProvider)
		{
			var sNewMessageIndicator = '<div class="ibo-navigation-menu--notifications--item--new-message-indicator"></div>';

			var sUnreadMessages = ''
			for(k in this.options.providers)
			{
				var sExtraMessages = '';
				if (aUnreadMessagesByProvider[k] > 0)
				{
					sExtraMessages = ' <span class="ibo-navigation-menu--notifications-show-all-multiple--counter">('+aUnreadMessagesByProvider[k]+')</span>'
				}
				sUnreadMessages += '<a class="ibo-popover-menu--item" data-provider-id="'+k+'" href="'+this.options.providers[k].view_all_url+'" target="_blank">'+ sNewMessageIndicator +this.options.providers[k].label+sExtraMessages+'</a>';
			}
			return '<a class="ibo-popover-menu--item ibo-navigation-menu--notifications-show-all-multiple" data-role="ibo-navigation-menu--notifications-show-all-multiple" href="#">'+this.options.labels.view_all+'<i class="fas fas-caret-down"></i></a>' +
				'<div class="ibo-popover-menu" data-role="ibo-popover-menu"><div class="ibo-popover-menu--section" data-role="ibo-popover-menu--section">'+sUnreadMessages+'</div></div>';
		},
		_buildMenu: function(aAllMessages)
		{
			var me = this;
			var iTotalCount = aAllMessages.length;
			var iCount = 0;
			var sDismissAllSection = this._buildDismissAllSection();
			var sMessageSection = this._buildMessageSection();
			var sShowAllMessagesSection = this._buildShowAllMessagesSection();

			moment.locale(GetUserLanguage());
			var aUnreadMessagesByProvider = [];
			for(var k in this.options.providers)
			{
				aUnreadMessagesByProvider[k] = 0;
			}
			for(var k in aAllMessages)
			{
				var oMessage = aAllMessages[k];
				aUnreadMessagesByProvider[oMessage.provider]++;
				if (iCount < this.options.display_limit + 4)
				{
					var sMessageItem = this._buildMessageItems(oMessage.id, oMessage.text, oMessage.image, oMessage.start_date, oMessage.provider, oMessage.url)
					//$(sMessageSection).append(sMessageItem);
					sMessageSection += sMessageItem;
				}
				iCount++;
			}

			if (iCount == 0)
			{
				var sNoMessageItem = this._buildNoMessageItem();
				sMessageSection += sNoMessageItem;
			}
			sMessageSection += '    <hr class="ibo-popover-menu--item ibo-popover-menu--separator"> </div>';
			
			if (this.options.providers.length == 1)
			{
				var SingleShowAllMessagesItem = this._buildSingleShowAllMessagesItem();	
				//$(sShowAllMessagesSection).append(SingleShowAllMessagesItem);
				sShowAllMessagesSection += SingleShowAllMessagesItem;
				sShowAllMessagesSection += '</div>'
			}
			else
			{
				var MultipleShowAllMessagesItem = this._buildMultipleShowAllMessagesItem(aUnreadMessagesByProvider);
				sShowAllMessagesSection += MultipleShowAllMessagesItem + '</div>'
			}
			if (iCount > 0)
			{
				$(this.element).html(sDismissAllSection + sMessageSection + sShowAllMessagesSection);
				$('.ibo-navigation-menu--notifications--item--content img').each(function(){
					tippy(this, {'content': this.outerHTML, 'placement': 'left', 'trigger': 'mouseenter focus', 'animation':'shift-away-subtle', 'allowHTML': true });
				});
				var me = this;
				//$('#newsroom_menu_counter').on('click', function() {setTimeout(function(){ $('#newsroom_menu_icon').trigger('click') }, 10);});
				//$('.newsroom_menu_item[data-msg-id]').on('click', function(ev) { me._handleClick(this); });
				$('[data-role="ibo-navigation-menu--notifications-item"]').on('click', function(oEvent){
					me._handleClick(this);
				});
				$('[data-role="ibo-navigation-menu--notifications-dismiss-all"]').on('click', function(ev) { me._markAllAsRead(); });
			}
			else
			{
				$(this.element).html(sMessageSection + sShowAllMessagesSection);
				var me = this;
			}
			
			if (this.options.providers.length != 1)
			{
				var oElem = $('[data-role="ibo-navigation-menu--notifications-show-all-multiple"]~[data-role="ibo-popover-menu"]');
				oElem.popover_menu({'toggler': '[data-role="ibo-navigation-menu--notifications-show-all-multiple"]'});

				$('[data-role="ibo-navigation-menu--notifications-show-all-multiple"]').on('click', function(oEvent){
					var oEventTarget = $(oEvent.target);
					var aEventTargetPos = oEventTarget.position();
					oElem.css({
						'left': (aEventTargetPos.left + parseInt(oEventTarget.css('marginLeft'), 10) + oEventTarget.width()) + 'px'
					});
					oElem.popover_menu("openPopup");
				});

			}
			
		},
		_handleClick: function(elem)
		{
			var me = this;
			var idxProvider = $(elem).attr('data-provider-id');
			var msgId = $(elem).attr('data-msg-id');
			
			this._markOneMessageAsRead(idxProvider, msgId);
			// window.open(sUrl, '_blank');
			// $('#newsroom_menu').remove();
			// $('#newsroom_menu_counter_container').remove();
			$(me.element).popover_menu("closePopup");
			this._getAllMessages();
		},
		_resetUnseenCount: function()
		{
			var display = $('#newsroom_menu_counter').css('display');
			$('#newsroom_menu_counter').fadeOut(500, function() {
				   $(this).css('visibility', 'hidden'); 
				   $(this).css('display', display);
				});
		},
		clearCache: function(idx)
		{
			if (idx == undefined)
			{
				for(var k in this.options.providers)
				{
					var sKey = this._makeCacheKey(k);
					localStorage.removeItem(sKey);			
				}				
			}
			else
			{
				var sKey = this._makeCacheKey(idx);
				localStorage.removeItem(sKey);			
			}
		},
		_makeCacheKey: function(idxProvider)
		{
			return this.options.cache_uuid+'_'+idxProvider;
		},
		_cacheData: function(idxProvider, oJSONData)
		{
			var sKey = this._makeCacheKey(idxProvider);
			var bSuccess = true;
			var oNow = new Date();
			var oExpirationDate = new Date(oNow.getTime() + this.options.providers[idxProvider].ttl * 1000);
			
			var oData = {value: JSON.stringify(oJSONData), expiration_date: oExpirationDate.getTime() };
			try
			{
				localStorage.setItem(sKey, JSON.stringify(oData))
			}
			catch(e)
			{
				console.warn('Newsroom: Failed to store newsroom messages into local storage !! reason: '+e);
				bSuccess = false;
			}
			return bSuccess;
		},
		_getCachedData: function(idxProvider)
		{
			var sKey = this._makeCacheKey(idxProvider);
			var sData = localStorage.getItem(sKey);
			if (sData == null) return null; // No entry in the local storage cache
			try
			{
				var oData = JSON.parse(sData);
				var oExpiration = new Date(oData.expiration_date);
				var oNow = new Date();
				if (oExpiration < oNow)
				{
					return null;
				}
				return JSON.parse(oData.value);
			}
			catch(e)
			{
				console.warn('Newsroom: Failed to fetch newsroom messages from local storage !! reason: '+e);
				this.clearCache(idxProvider);
				return null;
			}
		},
		_markOneMessageAsRead: function(idxProvider, msgId)
		{
			// Remove the given message from the cache
			var aData = this._getCachedData(idxProvider);
			if (aData !== null)
			{
				var aRemainingData = [];
				for(var k in aData)
				{
					var sId = aData[k].id.toString();
					if(sId !== msgId)
					{
						aRemainingData.push(aData[k]);
					}
				}
				this._cacheData(idxProvider, aRemainingData); // Also extends the TTL of the cache
			}
		},
		_markAllMessagesAsRead: function(idxProvider)
		{
			this._cacheData(idxProvider, []); //Store an empty list in the cache
			
			$.ajax({ type: "GET",
		        	 url: this.options.providers[idxProvider].mark_all_as_read_url,
		        	 async: true,
		        	 dataType : 'jsonp',
		        	 crossDomain: true,
		        	 jsonp: "callback"
		     })
		     .done(function(oJSONData) {
		    });
			
			
		},
		_markAllAsRead: function()
		{
			for(var k in this.options.providers)
			{
				this._markAllMessagesAsRead(k);
			}
			$('#newsroom_menu').html('<i class="top-right-icon '+this.options.image_icon+'" style="opacity:0.4" title="'+this.options.labels.no_message+'"></i>');
			$('#newsroom_menu_counter_container').remove();
			this._getAllMessages();
		}
	});	
});
