// pages/poster-poster/index.js
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '拼团海报',
      'color': true,
      'class': '0'
    },
    type:0,
    id:0,
    image:'',
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var that = this;
    if (options.hasOwnProperty('type') && options.hasOwnProperty('id')){
      that.setData({ type: options.type, id: options.id});
      if (options.type == 1) that.setData({ 'parameter.title':'砍价海报' }); 
      else that.setData({ 'parameter.title': '拼团海报' }); 
    }else{
      wx.showToast({
        title: '参数错误',
        icon: 'none',
        duration: 1000,
        mask: true,
      });
      setTimeout(function(){
        wx.navigateBack({ delta: 1 });
      },1200);
    }
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    this.getPosterInfo();
  },
  getPosterInfo:function(){
    var that = this,url = '';
    if (that.data.type == 1) url = app.U({ c: 'bargain_api', a: 'bargain_share_poster' });
    else url = app.U({ c: 'pink_api', a: 'pink_share_poster' });
    app.basePost(url, {id:that.data.id}, function (res) {
      that.setData({ image:res.data });
    }, function (res) { app.Tips({ 'title': res.msg});});
  },
  showImage:function(){
    var that = this;
    wx.previewImage({
      current: that.data.image,
      urls: [that.data.image],
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

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

  },
})