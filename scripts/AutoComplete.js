//
// Copyright (c) 2008 Beau D. Scott | http://www.beauscott.com
//
// Permission is hereby granted, free of charge, to any person
// obtaining a copy of this software and associated documentation
// files (the "Software"), to deal in the Software without
// restriction, including without limitation the rights to use,
// copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the
// Software is furnished to do so, subject to the following
// conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
// OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
// OTHER DEALINGS IN THE SOFTWARE.
//

/**
 * AutoComplete.js
 * Prototype/Scriptaculous based _suggest tool
 * @version 1.2
 * @requires prototype.js <http://www.prototypejs.org/>
 * @author Beau D. Scott <beau_scott@hotmail.com>
 * 6/5/2008
 */
var AutoComplete = Class.create({
	/**
	 * The select object
	 * @type {HTMLSelectElement}
	 */
	selector: null,
	/**
	 * The component that triggers the suggest
	 * @type {HTMLInputElement}
	 */
	input: null,
	/**
	 * The timeout between lookups
	 * @type {Number}
	 * @private
	 */
	_timeout: null,
	/**
	 * Visisbility status of the selector object
	 * @type {Boolean}
	 */
	visible: false,
	/**
	 * Flag indicating whether the components have been laid out
	 * @type {Boolean}
	 */
	drawn: false,

	/**
	 * Hide timeout
	 * @type {Number}
	 * @private
	 */
	_hideTimeout: null,

	/**
	 * The configuration options for the instance
	 * @type {AutoComplete.Options}
	 */
	options: null,

	/**
	 * @param {Object} input ID of form element, or dom element,  to _suggest on
	 * @param {String} URL of dictionary
	 * @param {Object} options
	 */
	initialize: function(input, action, options)
	{
		this.action = action;
		this.input = $(input);
		this.input.autocomplete = "off";
		this.options = new AutoComplete.Options(options || {});

		if(!this.input)
			alert('No input field/binding field given or found')

		if(!this.action)
			alert('No action url specified');

		this.selector = document.createElement('select');

		Event.observe(this.input, 'focus', this._onInputFocus.bindAsEventListener(this));
		//Event.observe(this.input, 'keyup', this._onInputKeyUp.bindAsEventListener(this));
		Event.observe(this.input, 'keydown', this._onInputKeyDown.bindAsEventListener(this));
		Event.observe(this.input, 'blur', this._onInputBlur.bindAsEventListener(this));
		Event.observe(this.selector, 'blur', this._onSelectorBlur.bindAsEventListener(this));
		Event.observe(this.selector, 'focus', this._onSelectorFocus.bindAsEventListener(this));
		Event.observe(this.selector, 'change', this._onSelectorChange.bindAsEventListener(this));

		Event.observe(window, 'resize', this._reposition.bind(this));
		Event.observe(window, 'scroll', this._reposition.bind(this));
	},

	/**
	 * The input fields focus event handler
	 */
	_onInputFocus: function(event)
	{
		this._onSelectorFocus(event);
	},

	/**
	 * The selector's blur event handler
	 * @param {Event} event
	 * @private
	 */
	_onSelectorBlur: function(event)
	{
		this._onInputBlur(event);
	},
	/**
	 * The input's blur event handler
	 * @param {Event} event
	 * @private
	 */
	_onInputBlur: function(event)
	{
		this._hideTimeout = setTimeout(this._checkOnBlur.bind(this), 100);
	},
	/**
	 * Complete's the blur event handlers. Used as a proxy to avoid event collisions when blurring from the input
	 * and focusing on the selector during a mouse navigation
	 * @private
	 */
	_checkOnBlur:function()
	{
		this._hideTimeout = null
		this.hide();
	},
	/**
	 * The input's key-up event handler
	 * @param {Event} event
	 * @private
	 */
	_onInputKeyUp: function(event)
	{
		this._suggest(event)
			&& Event.stop(event);
	},
	/**
	 * The input's key-down event handler
	 * @param {Event} event
	 * @private
	 */
	_onInputKeyDown: function(event)
	{
		this._suggest(event)
			&& Event.stop(event);
	},
	/**
	 * The selectors's focus event handler.
	 * @param {Event} event
	 * @private
	 */
	_onSelectorFocus: function(event)
	{
		if(this._hideTimeout)
		{
			clearTimeout(this._hideTimeout);
			this._hideTimeout = null;
		}
	},
	/**
	 * The selector's change event handler
	 * @param {Event} event
	 * @private
	 */
	_onSelectorChange: function(event)
	{
		this.select();
	},
	/**
	 * Lays the UI elements of the control out, sets interaction options
	 * @param {Object} event Event
	 */
	draw: function()
	{
		if(this.drawn) return;
		if(this.options.cssClass)
			this.selector.className = this.options.cssClass;
		Element.setStyle(this.selector, {
			display: 'none',
			position: 'absolute',
			width: this.input.offsetWidth + 'px'
		});
		this.selector.size = this.options.size;
		document.body.appendChild(this.selector);
		this.input.autocomplete = 'off';
		this.drawn = true;
	},

	/**
	 * Hides the option box
	 */
	hide: function()
	{
		if(!this.drawn || !this.visible) return;
		this.visible = false;
		if(window.Scriptaculous)
		{
			new Effect.BlindUp(this.selector, {
				duration: this.options.delay,
				queue: 'end',
				afterFinish: function(event){
					Element.setStyle(this.selector,{
						display: 'none'
					});
					this.selector.options.length = 0;
					setTimeout(this._restoreFocus.bind(this),50);
				}.bind(this)
			});
		}
		else
		{
			Element.setStyle(this.selector,{
				display: 'none'
			});
			this.selector.options.length = 0;
			// FF hack, wasn't selecting without this small delay for some reason
			setTimeout(this._restoreFocus.bind(this),50);
		}
	},
	/**
	 * Resores the focus to the input control to avoid the cursor getting lost somewhere.
	 * @private
	 */
	_restoreFocus: function() {
		this.input.focus();
	},
	/**
	 * Displays the select box
	 */
	show: function()
	{
		if(!this.drawn) this.draw();
		var trigger = null;
		if(this.selector.options.length)
		{
			if(window.Scriptaculous)
			{
				new Effect.BlindDown(this.selector,{
					duration: this.options.delay,
					queue: 'end'
				});
			}
			else
			{
				Element.setStyle(this.selector,{
					display: 'inline'
				});
			}
			this._reposition();
			this.visible = true;
		}
	},

	/**
	 * Removes the timeout function set by a suggest
	 * @private
	 */
	_cancelTimeout: function()
	{
		if(this._timeout)
		{
			clearTimeout(this._timeout);
			this._timeout = null;
		}
	},

	/**
	 * Triggers the suggest interaction
	 * @param {Object} event The interaction event (keyboard or mouse)
	 * @return {Boolean} Whether to stop the event
	 * @private
	 */
	_suggest: function(event)
	{
		this._cancelTimeout();
		var key = Event.keyPressed(event);
		var ignoreKeys = [
			20, // caps lock
			16, // shift
			17, // ctrl
			91, // Windows key
			121, // F1 - F12
			122,
			123,
			124,
			125,
			126,
			127,
			128,
			129,
			130,
			131,
			132,
			45, // Insert
			36, // Home
			35, // End
			33, // Page Up
			34, // Page Down
			144, // Num Lock
			145, // Scroll Lock
			44, // Print Screen
			19, // Pause
			93, // Mouse menu key
		];
		if(ignoreKeys.indexOf(key) > -1)
			return false;

		switch(key)
		{
			case Event.KEY_LEFT:
			case Event.KEY_RIGHT:
				return false;
				break;
			case Event.KEY_TAB:
			case Event.KEY_BACKSPACE:
			case 46: //Delete
				this.cancel();
				return false;
				break;
			case Event.KEY_RETURN:
				if(this.visible)
				{
					this.select();
					return true;
				}
				return false;
				break;
			case Event.KEY_ESC:
				this.cancel();
				return true;
				break;
			case Event.KEY_UP:
			case Event.KEY_DOWN:
				this._interact(event);
				return true;
				break;
			default:
				break;
		}

		if(this.input.value.length >= this.options.threshold - 1)
		{
			this._timeout = setTimeout(this._sendRequest.bind(this), 1000 * this.options.delay);
		}
		return false;
	},

	/**
	 * Sends the suggestion request
	 * @private
	 */
	_sendRequest: function()
	{
		this._request = new Ajax.Request(this.action + this.input.value, {
			onComplete: this._process.bind(this),
			method: this.options.requestMethod
		});
	},

	/**
	 * Repositions the selector (if visible) to match the new
	 * coords of the input.
	 * @private
	 */
	_reposition: function()
	{
		if(!this.drawn) return;
		var pos = Position.cumulativeOffset(this.input);
		pos.push(pos[0] + this.input.offsetWidth);
		pos.push(pos[1] + this.input.offsetHeight);
		Element.setStyle(this.selector,{
			left: pos[0] + 'px',
			top: pos[3] + 'px'
		});
	},

	/**
	 * Processes the resulting  from a suggestion request, adds options to the suggestion box.
	 * @param {XMLHTTPRequest} objXML The XMLHTTPRequest created by _sendRequest
	 * @param {String} jsonHeader the string of json commands to execute if the return type is json
	 * @private
	 */
	_process: function(objXML, jsonHeader)
	{
		this.selector.options.length = 0;
		switch(this.options.resultFormat)
		{
			case AutoComplete.Options.RESULT_FORMAT_XML:
				this._parseXML(objXML.responseXML);
				break;
			case AutoComplete.Options.RESULT_FORMAT_JSON:
				if(!jsonHeader)
				{
					jsonHeader = objXML.responseText && objXML.responseText.isJSON() ?
						objXML.responseText.evalJSON() : null;
				}
				this._parseJSON(jsonHeader);
				break;
			case AutoComplete.Options.RESULT_FORMAT_TEXT:
				this._parseText(objXML.responseText);
				break;
			default:
				alert("Unable to parse result type. Make sure you've set the resultFormat option correctly");
				break;
		}

		if(this.selector.options.length > (this.options.size))
			this.selector.size = this.options.size;
		else
			this.selector.size = this.selector.options.length > 1 ? this.selector.options.length : 2;

		if(this.selector.options.length)
		{
			//none selected by default
			this.selector.selectedIndex = -1;
			this.show();
		}
		else
			this.cancel();
	},

	/**
	 * Parses the XML result, adds options
	 * @param {XML} xml the XML of suggestions to parse
	 */
	_parseXML: function(xml)
	{
		var suggestions = null;
		for(var i = 0; i < xml.childNodes.length; i++)
		{
			if(xml.childNodes[i].tagName)
			{
				suggestions = xml.childNodes[i].childNodes;
			}
		}
		if(!suggestions)
		{
			alert("Could not parse response XML.");
			return;
		}
		for(i = 0; i < suggestions.length; i++)
		{
			suggestion = suggestions.item(i).firstChild.nodeValue;
			this._addOption(suggestion);
		}
	},
	/**
	 * Parses the JSON result, adds options
	 * @param {String} json The json response to parse
	 */
	_parseJSON: function(json)
	{
		if(!json) json = [];
		for(i = 0; i < json.length; i++)
			this._addOption(json[i]);
	},
	/**
	 * Parses the TEXT result, adds options
	 * @param {String} text The text response to parse
	 */
	_parseText: function(text)
	{
		var suggestions = (text||"").split(/\n/);
		for(i = 0; i < suggestions.length; i++)
			this._addOption(suggestions[i]);
	},
	/**
	 * Creates a suggestion option for the given suggestion,
	 * adds it to the selector object.
	 * @param {String} suggestion The suggestion
	 */
	_addOption: function(suggestion)
	{
		var opt = new Option(suggestion, suggestion);
		Prototype.Browser.IE ? this.selector.add(opt) : this.selector.add(opt, null);
	},

	/**
	 * Clears and hides the suggestion box.
	 */
	cancel: function()
	{
		this.hide();
	},

	/**
	 * Captures the currently selected suggestion option to the input field
	 */
	select: function()
	{
		if(this.selector.options.length)
			this.input.value = this.selector.options[this.selector.selectedIndex].value;
		this.cancel();
		if(typeof this.options.onSelect == 'function')
		{
			this.options['onSelect'](this.input);
		}
	},

	/**
	 * Processes key interactions with the input field, including navigating the selected option
	 * with the up/down arrows, esc cancelling and selecting the option.
	 * @param {Event} event The interaction event
	 * @private
	 */
	_interact: function(event)
	{
		if(!this.visible) return;

		var key = Event.keyPressed(event);
		if(key != Event.KEY_UP && key != Event.KEY_DOWN) return;
		var mx = this.selector.options.length;

		if(key == Event.KEY_UP)
		{
			if(this.selector.selectedIndex == 0)
				this.selector.selectedIndex = this.selector.options.length - 1;
			else
				this.selector.selectedIndex--;
		}
		else
		{
			if(this.selector.selectedIndex == this.selector.options.length - 1)
				this.selector.selectedIndex = 0;
			else
				this.selector.selectedIndex++;
		}
	}
});

/**
 * Helper class for defining options for the AutoComplete object
 * @version 1.2
 * @requires prototype.js <http://www.prototypejs.org/>
 * @author Beau D. Scott <beau_scott@hotmail.com>
 * 6/5/2008
 */
AutoComplete.Options = Class.create({
	/**
	 * Number of options to display before scrolling
	 * @type {Number}
	 */
	size: 10,
	/**
	 * CSS class name for autocomplete selector
	 * @type {String}
	 */
	cssClass: null,
	/**
	 * JavaScript callback function to execute upon selection
	 * @type {Function}
	 */
	onSelect: null,
	/**
	 * Minimum characters needed before an suggestion is executed
	 * @type {Number}
	 */
	threshold: 3,
	/**
	 * Time delay between key stroke and execution
	 * @type {Number}
	 */
	delay: .2,
	/**
	 * The request method to use when getting the suggestions
	 * @type {String}
	 */
	requestMethod: 'GET',
	/**
	 * The format of the results retrieved (xml, text or json)
	 * @type {String}
	 */
	resultFormat: 'xml',
	/**
	 * Constructor
	 * @type {Object} overrides Overriding properties
	 */
	initialize: function(overrides)
	{
		Object.extend(this, overrides || {});
	}
});
Object.extend(AutoComplete.Options, {
	/**
	 * Enumeration for XML result format
	 * Be sure your response type is application/xml or text/xml
	 * @static
	 */
	RESULT_FORMAT_XML: 'xml',
	/**
	 * Enumeration for JSon result format
	 * @static
	 */
	RESULT_FORMAT_JSON: 'json',
	/**
	 * Enumeration for text result format
	 * @static
	 */
	RESULT_FORMAT_TEXT: 'text'
});

//
// Various Prototype Event extensions
//
Object.extend(Event, {
	/**
	 * Enumeration for the backspace key code
	 * @type {Number}
	 * @static
	 */
	KEY_BACKSPACE: 8,
	/**
	 * Enumeration for the tab key code
	 * @static
	 */
	KEY_TAB:       9,
	/**
	 * Enumeration for the return/enter key code
	 * @static
	 */
	KEY_RETURN:   13,
	/**
	 * Enumeration for the escape key code
	 * @static
	 */
	KEY_ESC:      27,
	/**
	 * Enumeration for the left arrow key code
	 * @static
	 */
	KEY_LEFT:     37,
	/**
	 * Enumeration for the up arrow key code
	 * @static
	 */
	KEY_UP:       38,
	/**
	 * Enumeration for the right arrow key code
	 * @static
	 */
	KEY_RIGHT:    39,
	/**
	 * Enumeration for the down arrow key code
	 * @static
	 */
	KEY_DOWN:     40,
	/**
	 * Enumeration for the delete key code
	 * @static
	 */
	KEY_DELETE:   46,
	/**
	 * Enumeration for the shift key code
	 * @static
	 */
	KEY_SHIFT:    16,
	/**
	 * Enumeration for the cotnrol key code
	 * @static
	 */
	KEY_CONTROL:  17,
	/**
	 * Enumeration for the capslock key code
	 * @static
	 */
	KEY_CAPSLOCK: 20,
	/**
	 * Enumeration for the space key code
	 * @static
	 */
	KEY_SPACE:	  32,

	/**
	 * A simple interface to get the key code of the key pressed based, browser sensitive.
	 * @param {Event} event They keyboard event
	 * @return {Number} the key code of the key pressed
	 */
	keyPressed: function(event)
	{
		return Prototype.Browser.IE ? window.event.keyCode : event.which;
	}
});
