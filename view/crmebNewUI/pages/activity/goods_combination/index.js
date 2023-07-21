// pages/group-list/index.js
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '拼团列表',
      'color': true,
      'class': '0'
    },
    combinationList: [],
    limit: 20,
    offset: 0,
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
  },
  onLoadFun: function () {
    this.getCombinationList();
  },
  getCombinationList:function(){
    var that = this;
    if (that.data.status) return;
    var data = { offset: that.data.offset, limit: that.data.limit};
    app.basePost(app.U({ c: 'pink_api', a: "get_combination_list" }), data,function (res) {
        var combinationList = that.data.combinationList;
        var limit = that.data.limit;
        var offset = that.data.offset;
        that.setData({
          status: limit > res.data.length,
          combinationList: combinationList.concat(res.data),
          offset: Number(offset) + Number(limit)
        });
      },function(res){console.log(res);});
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
    this.getCombinationList();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})