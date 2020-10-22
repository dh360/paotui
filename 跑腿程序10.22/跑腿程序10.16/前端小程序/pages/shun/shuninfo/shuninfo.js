var app = getApp();

Page({
    data: {
        comment: !0
    },
    onLoad: function(n) {
      app.setNavigationBarColor(this);
    },
    comment: function(n) {
        this.setData({
            comment: !1
        });
    },
    cancel: function(n) {
        this.setData({
            comment: !0
        });
    },
    onReady: function() {},
    onShow: function() {},
    onHide: function() {},
    onUnload: function() {},
    onPullDownRefresh: function() {},
    onReachBottom: function() {},
    onShareAppMessage: function() {}
});