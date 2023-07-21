// pages/bargain-list/index.js
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    topList:[],
    bargainList:[],
    offset:0,
    limit:20,
    status:false,
    userInfo:[],
    navH:''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      navH: app.globalData.navHeight
    });
    console.log(app.globalData.navHeight+'aa');
  },
  goBack:function(){
    wx.navigateBack({ delta: 1 })
  },
  onLoadFun: function () {
    this.getBargainConfig();
    this.getBargainList();
    this.getUserInfo();
  },
  getUserInfo: function () {
    var that = this;
    app.baseGet(app.U({ c: 'user_api', a:'get_my_user_info'}),function(res){
      that.setData({ userInfo:res.data });
    },function(res){ console.log(res) });
  },
  getBargainConfig: function () {
    var that = this;
    app.baseGet(app.U({ c: 'bargain_api', a:'get_bargain_config'}),function(res){
      that.setData({ topList:res.data })
    },function(ress){ console.log(res);});
  },
  getBargainList:function(){
    var that = this;
    if (that.data.status) return;
    var offset = that.data.offset;
    var limit = that.data.limit;
    var data = { offset: offset, limit:limit};
    app.basePost(app.U({ c: 'bargain_api', a: 'get_bargain_list' }), data, function (res) {
      that.setData({ 
        bargainList: res.data,
        offset: Number(offset) + Number(limit),
        status: limit > res.data.length,
      });
     }, function (ress) { console.log(res); });
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

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
    this.getBargainList();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})