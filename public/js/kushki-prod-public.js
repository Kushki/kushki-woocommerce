/**
 * Created by zerocooljs on 12/7/16.
 */
var KushkiCheckout, bind = function (t, e) {
    return function () {
        return t.apply(e, arguments)
    }
};
KushkiCheckout = function () {
    function t(t, e) {
        this.params = null != t ? t : {};
        this.onReceiveMessage = bind(this.onReceiveMessage, this);
        this.url = e || "https://cdn.kushkipagos.com/index.html";
        this.id = +new Date;
        this.iframeHeightOffset = 10;
        this.form = document.getElementById(this.params.form);
        this.iframe = document.createElement("iframe");
        this._loadIframe();
        this._listenForMessages();
    }

    return t.prototype.onReceiveMessage = function (t) {
        if (t.origin === this.expectedOrigin())return this.processMessage(t.data)
    }, t.prototype.expectedOrigin = function () {
        var t;
        return t = this.url.split("/"), t[0] + "//" + t[2]
    }, t.prototype.processMessage = function (t) {
        var e, i, s;
        switch (s = t.split(":"), i = s[0], e = s[1], i) {
            case"height":
                return this._adjustHeight(e);
            case"parameters":
                return this._setParameters(e)
        }
    }, t.prototype._loadIframe = function () {
        var t, e, i, s;
        i = this.url + "?merchant_id=" + this.params.merchant_id + ("&is_subscription=" + this.params.is_subscription) + ("&amount=" + this.params.amount) + ("&language=" + this.params.language) + ("&currency=" + this.params.currency);
        t = {
            src: i,
            width: "100%",
            style: "display:block",
            name: "kushki-iframe",
            id: this.id,
            scrolling: "no",
            frameborder: "0"
        };
        for (e in t)s = t[e], this.iframe.setAttribute(e, s);
        return this.form.appendChild(this.iframe)
    }, t.prototype._listenForMessages = function () {
        return window.addEventListener("message", this.onReceiveMessage, !1)
    }, t.prototype._createInput = function (t, e) {
        var i, s, r, n;
        s = document.createElement("input"), i = {type: "hidden", name: e, value: t.trim()};
        for (r in i)n = i[r], s.setAttribute(r, n);
        return s
    }, t.prototype._adjustHeight = function (t) {
        return this.iframe.height = this.iframeHeightOffset + parseInt(t, 10)
    }, t.prototype._setParameters = function (t) {
        var e, i, s, r, n;
        return r = t.split(",");
        n = r[0];
        e = r[1];
        s = this._createInput(n, "kushkiToken");
        i = this._createInput(e, "kushkiDeferred");
        this.form.appendChild(s);
        this.form.appendChild(i);
        this.form.submit()
    }, t
}();// KushkiVersion 221
