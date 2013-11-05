//hotkey
hotkey_actions['mobilize'] = function() {
  if (getActiveArticleId()) {
    mobilizeArticle(getActiveArticleId());
    return;
  }
};

//iframe
function mobilizeArticle(id) {
	try {
		var hasSandbox = "sandbox" in document.createElement("iframe");

		if (!hasSandbox) {
			alert(__("Sorry, your browser does not support sandboxed iframes."));
			return;
		}

		var query = "op=pluginhandler&plugin=mobilize&method=getUrl&id=" +param_escape(id);

		var c = false;

		if (isCdmMode()) {
			c = $$("div#RROW-" + id + " div[class=cdmContentInner]")[0];
		} else if (id == getActiveArticleId()) {
			c = $$("div[class=postContent]")[0];
		}

		if (c) {
			var iframe = c.parentNode.getElementsByClassName("embeddedContent")[0];

			if (iframe) {
				Element.show(c);
				c.parentNode.removeChild(iframe);

				if (isCdmMode()) {
					cdmScrollToArticleId(id, true);
				}

				return;
			}
		}

		new Ajax.Request("backend.php",	{
			parameters: query,
			onComplete: function(transport) {
				var ti = JSON.parse(transport.responseText);

				if (ti) {
					var iframe = new Element("iframe", {
						class: "embeddedContent",
						src: 'plugins/mobilize/m.php?'+ti.url,
						width: (c.parentNode.offsetWidth-5)+'px',
						height: (c.parentNode.parentNode.offsetHeight-c.parentNode.firstChild.offsetHeight-5)+'px',
//						height: (c.parentNode.parentNode.offsetHeight-c.parentNode.firstChild.offsetHeight-15)+'px',
						style: "overflow: auto; border: none; min-height: "+(document.body.clientHeight/2)+"px;",
//						style: "overflow: scroll; border: none; min-height: "+(document.body.clientHeight-180)+"px; max-height: "+(document.body.clientHeight-80)+"px;",
//						style: "overflow: scroll; border: none; min-height: "+(document.body.clientHeight-214)+"px; max-height: "+(document.body.clientHeight-85)+"px;",
						sandbox: 'allow-same-origin allow-scripts allow-popups allow-forms',
					});

					if (c) {
						Element.hide(c);
						c.parentNode.insertBefore(iframe,c);

						if (isCdmMode()) {
							cdmScrollToArticleId(id, true);
						}
					}
				}

			} });


	} catch (e) {
		exception_error("autoEmbedOriginalArticle", e);
	}
}

