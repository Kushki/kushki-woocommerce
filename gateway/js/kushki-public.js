(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
	 *
	 * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
	 *
	 * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

})(jQuery);

var KushkiCheckout, bind = function (e, t) {
    return function () {
        return e.apply(t, arguments)
    }
};
KushkiCheckout = function () {
    function Kushki(e, t) {
        this.params = null != e ? e : {};
        this.onReceiveMessage = bind(this.onReceiveMessage, this);
        this.url = t || "https://cdn-uat.kushkipagos.com/index.html";
        this.id = +new Date;
        this.iframeHeightOffset = 10;
        this.element = document.getElementById(this.params.form);
        this.form = jQuery(this.element).closest("form");
        this.iframe = document.createElement("iframe");
        this.loadIframe();
        this.listenForMessages();
    }

    Kushki.prototype.loadIframe = function () {
        var e, t, i, r;
        i = this.url + ("?merchant_id=" + this.params.merchant_id) + ("&is_subscription=" + this.params.is_subscription)  + ("&amount=" + this.params.amount) + ("&language=" + this.params.language) + ("&currency=" + this.params.currency);
        e = {
            src: i,
            width: "100%",
            style: "display:block",
            name: "kushki-iframe",
            id: this.id,
            scrolling: "no",
            frameborder: "0"
        };
        for (t in e) {
            r = e[t];
            this.iframe.setAttribute(t, r);
        }

        return this.element.appendChild(this.iframe)
    };

    Kushki.prototype.listenForMessages = function () {
        return window.addEventListener("message", this.onReceiveMessage, !1)
    };

    Kushki.prototype.onReceiveMessage = function (e) {
        if (e.origin === this.expectedOrigin())return this.processMessage(e.data)
    };

    Kushki.prototype.expectedOrigin = function () {
        var e;
        return e = this.url.split("/"), e[0] + "//" + e[2]
    };

    Kushki.prototype.adjustHeight = function (e) {
        return this.iframe.height = this.iframeHeightOffset + parseInt(e, 10)
    };

    Kushki.prototype.setParameters = function (e) {
        var t, i, r, s, n;
        s = e.split(",");
        n = s[0];
        t = s[1];
        r = this.createInput(n, "kushkiToken");
        i = this.createInput(t, "kushkiDeferred");
        this.form[0].appendChild(r);
        this.form[0].appendChild(i);
        this.form.submit();
        return s;
    };

    Kushki.prototype.createInput = function (e, t) {
        var i, r, s, n;
        r = document.createElement("input"), i = {type: "hidden", name: t, value: e.trim()};
        for (s in i)n = i[s], r.setAttribute(s, n);
        return r
    };

    Kushki.prototype.processMessage = function (e) {
        var t, i, r;
        switch (r = e.split(":"), i = r[0], t = r[1], i) {
            case"height":
                return this.adjustHeight(t);
            case"parameters":
                return this.setParameters(t)
        }
    };

    return Kushki;
}();

// KushkiVersion 201

