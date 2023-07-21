// pages/bargain/index.js
var app = getApp();
const wxh = require('../../../utils/wxh.js');
const wxParse = require('../../../wxParse/wxParse.js');
const util = require('../../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    countDownDay: '00',
    countDownHour: '00',
    countDownMinute: '00',
    countDownSecond: '00',
    active: false,
    id:0,//砍价产品编号
    userInfo:0,//当前用户信息
    bargainUid: 0,//开启砍价用户
    bargainUserInfo: [],//开启砍价用户信息
    bargainUserId: 0,//开启砍价编号
    bargainInfo:[],//砍价产品
    offset:0,
    limit:20,
    limitStatus:false,
    bargainUserHelpList:[],
    bargainUserHelpInfo:[],
    bargainUserBargainPrice:0,
    status:'', // 0 开启砍价   1  朋友帮忙砍价  2 朋友帮忙砍价成功 3 完成砍价  4 砍价失败 5已创建订单
    bargainCount:[],//分享人数  浏览人数 参与人数
    retunTop:true,
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this;
    var pages = getCurrentPages();
    if (pages.length <= 1) that.setData({ retunTop:false});
    //扫码携带参数处理
    if (options.scene) {
      var value = util.getUrlParams(decodeURIComponent(options.scene));
      if (value.id) options.id = value.id;
      if (value.bargain) options.bargain = value.bargain;
      //记录推广人uid
      if (value.pid) app.globalData.spid = value.pid;
    }
    if (options.hasOwnProperty('id') && options.hasOwnProperty('bargain')) {
      if (options.bargain > 0) {
        this.setData({ id: options.id, bargainUid: options.bargain });
        app.globalData.openPages = '/pages/activity/goods_bargain_details/index?id=' + this.data.id + '&bargain=' + that.data.bargainUid;
      } else {
        wx.showToast({
          title: '参数错误',
          icon: 'none',
          duration: 1000,
          mask: true,
        });
        setTimeout(function () { wx.navigateBack({ delta: 1 }) }, 1200)
      };
    } else {
      wx.showToast({
        title: '参数错误',
        icon: 'none',
        duration: 1000,
        mask: true,
      });
      setTimeout(function () { wx.navigateBack({ delta: 1 }) }, 1200)
    };
  },
  goBack: function () {
    wx.navigateBack({ delta: 1 })
  },
  gobargainUserInfo:function(){//获取开启砍价用户信息
    var that = this;
    var data = { userId: that.data.bargainUid };
    app.basePost(app.U({ c: 'user_api', a: 'get_user_info_uid' }), data, function (res) {
      that.setData({ bargainUserInfo:res.data });
    }, function (res) { console.log(res); })
  },
  goPay: function () {//立即支付
    var that = this;
    var data = { 
      productId: that.data.bargainInfo.product_id,
      bargainId: that.data.id, 
      cartNum: that.data.bargainInfo.num,
      uniqueId:'',
      combinationId:0,
      secKillId:0,
    };
    app.basePost(app.U({ c: 'auth_api', a: 'now_buy' }), data, function (res) {
      wx.navigateTo({ url: '/pages/order_confirm/index?cartId=' + res.data.cartId });
    }, function (res) { console.log(res); })
  },
  getBargainUserStatus:function(){//获取砍价状态
     var that = this;
    var data = { bargainId: that.data.id, bargainUserUid: that.data.bargainUid };
    app.basePost(app.U({ c: 'bargain_api', a: 'set_status' }), data, function (res) {
      that.setStatus(res.data);
    }, function (res) { console.log(res); })
  },
  setStatus:function(status){//设置砍价状态
    this.setData({ status:status });
  },
  getBargainDetails:function(){//获取砍价产品详情
    var that = this;
    var data = {bargainId:that.data.id};
    app.basePost(app.U({ c: 'bargain_api', a:'get_bargain'}),data,function(res){
      that.setData({ 
        bargainInfo: res.data.bargain, 
        bargainPrice:res.data.bargain.price,
        userInfo: res.data.userInfo, 
        bargainSumCount: res.data.bargainSumCount 
      });
      app.globalData.openPages = '/pages/activity/goods_bargain_details/index?id=' + that.data.id + '&bargain=' + that.data.bargainUid + '&scene=' + that.data.userInfo.uid;
      wxParse.wxParse('description', 'html', that.data.bargainInfo.description || '', that, 0); 
      wxParse.wxParse('rule', 'html', that.data.bargainInfo.rule || '', that, 0); 
      wxh.time2(that.data.bargainInfo.stop_time, that);
      if (that.data.userInfo.uid == that.data.bargainUid) that.setBargain();
      else {
        that.getBargainHelpCount();
        that.getBargainUserStatus();
        that.setData({ bargainUserHelpList:[]});
        that.getBargainUser();
        that.gobargainUserInfo();
      }
    },function(res){ console.log(res); })
  },
  getBargainHelpCount: function () {//获取砍价帮总人数、剩余金额、进度条、已经砍掉的价格
    var that = this;
    var data = { bargainId: that.data.id, bargainUserUid:that.data.bargainUid };
    app.basePost(app.U({ c: 'bargain_api', a: 'get_bargain_help_count' }), data, function (res) {
      var price = util.$h.Sub(that.data.bargainPrice, res.data.alreadyPrice);
      that.setData({ 
        bargainUserHelpInfo: res.data, 
        'bargainInfo.price': parseFloat(price) <= 0 ? 0 : price,
      });
    },function (res) { console.log(res); })
  },
  currentBargainUser:function(){//当前用户砍价
    this.setData({ bargainUid:this.data.userInfo.uid });
    this.setBargain();
  },
  setBargain:function(){//参与砍价
    var that = this;
    var data = { bargainId: that.data.id };
    app.basePost(app.U({ c: 'bargain_api', a: 'set_bargain' }), data, function (res) {
      that.setData({ bargainUserId: res.data });
      that.getBargainUserStatus();
      that.getBargainUserBargainPrice();
      that.setBargainHelp();
      that.getBargainHelpCount();
    }, function (res) { console.log(res); })
  },
  setBargainHelp: function () {//帮好友砍价
    var that = this;
    var data = { bargainId: that.data.id, bargainUserUid: that.data.bargainUid };
    app.basePost(app.U({ c: 'bargain_api', a: 'set_bargain_help' }), data, function (res) {
      that.setData({ bargainUserHelpList: [] });
      that.getBargainUser(); 
      that.getBargainUserBargainPrice();
      that.getBargainUserStatus();
      that.getBargainHelpCount();
    }, function (res) {that.setData({ bargainUserHelpList: [] });that.getBargainUser();console.log(res); })
  },
  getBargainUser: function () {//获取砍价帮
    var that = this;
    var data = { 
      bargainId: that.data.id, 
      bargainUserUid: that.data.bargainUid,
      offset: that.data.offset,
      limit: that.data.limit,
    };
    app.basePost(app.U({ c: 'bargain_api', a: 'get_bargain_user' }), data, function (res) {
      var bargainUserHelpListNew = [];
      var bargainUserHelpList = that.data.bargainUserHelpList;
      var len = res.data.length;
      bargainUserHelpListNew = bargainUserHelpList.concat(res.data);
      that.setData({ bargainUserHelpList: bargainUserHelpListNew, limitStatus: data.limit > len, offest: Number(data.offset) + Number(data.limit)});
     }, function (res) { console.log(res); });
  },
  getBargainUserBargainPricePoster:function(){
    var that = this;
    wx.navigateTo({
      url: '/pages/activity/poster-poster/index?type=1&id=' + that.data.id,
    });
  },
  getBargainUserBargainPrice: function () {//获取帮忙砍价砍掉多少金额
    var that = this;
    var data = {bargainId: that.data.id,bargainUserUid: that.data.bargainUid};
    app.basePost(app.U({ c: 'bargain_api', a: 'get_bargain_user_bargain_price' }), data, function (res) {
      that.setData({ bargainUserBargainPrice: res.data, active:true });
    }, function (res) { 
      console.log(22);
      that.setData({active:false});
     });
  },
  goBargainList:function(){
     wx.navigateTo({
       url: '/pages/activity/goods_bargain/index',
     })
  },
  close:function(){
    this.setData({
      active: false
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },
  onLoadFun: function () {
    this.getBargainDetails();
    this.addLookBargain();
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    if (this.data.interval) clearInterval(this.data.interval);
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },
  addLookBargain: function () {//添加砍价浏览次数 获取人数
    var that = this;
    var data = { bargainId: this.data.id };
    app.basePost(app.U({c:'bargain_api',a:'add_look_bargain' }), data, function(res){
      that.setData({ bargainCount: res.data })
    },function(res){});
  },
  addShareBargain: function () {//添加分享次数 获取人数
    var that = this;
    var data = { bargainId: this.data.id };
    app.basePost(app.U({c:'bargain_api',a:'add_share_bargain'}), data, function(res){
      that.setData({ bargainCount: res.data })
    },function (res){});
  },
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    var that = this;
    that.close();
    that.addShareBargain();
    return {
      title: '您的好友' + that.data.userInfo.nickname + '邀请您帮他砍' + that.data.bargainInfo.title+' 快去帮忙吧！',
      path: app.globalData.openPages,
      imageUrl: that.data.bargainInfo.image,
    }
  }
})