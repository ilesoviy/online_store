// pages/bargain-record/index.js
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '砍价记录'
    },
    bargainList:[],
    page:0,
    limit:20,
    status:false,
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

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
    this.bargainList();
  },
  cancelBargain: function (event){
    var item = event.currentTarget.dataset.item;
    var that = this;
    app.basePost(app.U({ c: 'bargain_api', a: 'cancel_bargain' }), { bargainId: item.bargain_id},function(res){
      app.Tips({title:res.msg,icon:'success'});
      clearInterval(that.data.timer);
      that.setData({ 
        timer:'',
        page:0,
        status:false,
        bargainList: [] 
      });
      that.bargainList();
    })
  },
  toBargainList: function () {
    wx.navigateTo({
      url: '/pages/activity/goods_bargain/index',
    })
  },
  toBargain:function(event){
     wx.navigateTo({
       url: '/pages/activity/goods_bargain_details/index?id='+event.currentTarget.dataset.item.bargain_id+'&bargain='+event.currentTarget.dataset.item.uid,
     })
  },
  bargainList: function () {
    var that = this;
    var bargainList = that.data.bargainList;
    var timer = that.data.timer; 
    var page = that.data.page; 
    var limit = that.data.limit;
    var status = that.data.status;
    var bargainListNew = [];
    if (status == true) return ;
    app.baseGet(app.U({ c: 'bargain_api', a: 'bargain_list', q: { page: page, limit: limit } }), function (res) {
      var len = res.data.length;
      var bargainListData = res.data;
      bargainListNew = bargainList.concat(bargainListData);
      if (timer != 0) clearInterval(timer);
      that.setData({ 
        bargainList: bargainListNew, 
        timer: 0, 
        status: limit > len,
        page: Number(page) + Number(limit)
      });
      that.setTime();
    });
  },
  setTime:function(){
    var that = this;
    var bargainList = that.data.bargainList;
    var len = bargainList.length;//时间数据长度
    function nowTime() {//时间函数
      for (var i = 0; i < len; i++) {
        var intDiff = bargainList[i].datatime - Date.parse(new Date()) / 1000;
        //获取数据中的时间戳的时间差；
        var day = 0, hour = 0, minute = 0, second = 0;
        if (intDiff > 0) {//转换时间
          day = Math.floor(intDiff / (60 * 60 * 24));
          hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
          minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
          second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
          if (hour <= 9) hour = '0' + hour;
          if (minute <= 9) minute = '0' + minute;
          if (second <= 9) second = '0' + second;
          day = day;
          hour = hour;
          minute = minute;
          second = second;
        } else {
          day = "00";
          hour = "00";
          minute = "00";
          second = "00";
        }
        bargainList[i].day = day;//在数据中添加difftime参数名，把时间放进去
        bargainList[i].hour = hour;
        bargainList[i].minute = minute;
        bargainList[i].second = second;
      }
      that.setData({ bargainList: bargainList });
    }
    nowTime();
    var timer = setInterval(nowTime, 1000);
    that.setData({ timer: timer });
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
    clearInterval(this.data.timer);
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})