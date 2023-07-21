// pages/group-con/index.js
const app = getApp();
const wxh = require('../../../utils/wxh.js');
const util = require('../../../utils/util.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    parameter: {
      'navbar': '1',
      'return': '1',
      'title': '开团',
      'color': false,
    },
    countDownHour:'00',
    countDownMinute:'00',
    countDownSecond:'00',
    iShidden: false,
    count:0,//还差多少人拼团完成
    isOk:0,//是否拼团完成
    pinkAll:[],//当前拼团列表
    pinkBool:0,
    pinkT:{},//团长信息
    storeCombination:{},//当前拼团产品详情
    userBool:0,//是否为本人开团
    current_pink_order:'',//当前订单号
    userInfo:{},
    isClose:0,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    //扫码携带参数处理
    if (options.scene) {
      var value = util.getUrlParams(decodeURIComponent(options.scene));
      if (value.id) options.id = value.id;
      //记录推广人uid
      if (value.pid) app.globalData.spid = value.pid;
    }
    if(!options.id) return app.Tips({title:'缺少参数'},{tab:3,url:1});
    this.setData({pinkId: options.id});
  },

  /**
   * 授权回调
  */
  onLoadFun:function(){
    this.getPink();
  },
  // 打开海报页面
  getPinkPoster: function () {
    var that = this;
    wx.navigateTo({
      url: '/pages/activity/poster-poster/index?type=2&id=' + that.data.pinkId,
    });
  },
  /**
   * 获取拼团
  */
  getPink:function(){
    var that=this;
    app.baseGet(app.U({ c: 'pink_api', a: 'get_pink', q: { id: that.data.pinkId}}),function(res){
      var title ='开团';
      that.setData({
        count: parseInt(res.data.count),
        isOk: res.data.is_ok,
        pinkAll: res.data.pinkAll,
        current_pink_order: res.data.current_pink_order,
        pinkBool: res.data.pinkBool,
        pinkT: res.data.pinkT,
        storeCombination: res.data.store_combination,
        storeCombinationHost: res.data.store_combination_host,
        userBool: res.data.userBool,
        userInfo: res.data.userInfo
      });
      
      if (that.data.isOk && !that.data.count){
        title ='拼团成功，等待商家发货';//拼团完成
      } else if (that.data.isOk && that.data.count){
        title = '拼团失败';//拼团失败
      } else if (that.data.userBool && !that.data.isOk){
        title = that.data.pinkT.uid == that.data.userInfo.uid ? '开团成功' : '拼团成功';//本人开团成功
        wxh.time(that.data.pinkT.stop_time, that);
      } else if (!that.data.userBool && !that.data.isOk){
        title = '参团';//本人参团
        wxh.time(that.data.pinkT.stop_time, that);
      }
      that.setData({'parameter.title':title});
    });
  },
  /**
   * 再次开团
  */
  againPink:function(){
    return app.Tips('/pages/activity/goods_combination_details/index?id='+this.data.storeCombination.id);
  },
  /**
   * 参团
  */
  goPinkOrder:function(e){
    var formId = e.detail.formId, that = this;
    app.baseGet(app.U({ c: "public_api", a: 'get_form_id', q: { formId: formId}}),null,null,true);
    app.baseGet(app.U({ c: 'auth_api', a:'now_buy',q:{
      productId: that.data.storeCombination.product_id,
      cartNum: that.data.pinkT.total_num,
      uniqueId: '',
      combinationId: that.data.storeCombination.id,
      secKillId: 0
    }}),function(res){
      return app.Tips('/pages/order_confirm/index?cartId=' + res.data.cartId +'&pinkId='+that.data.pinkT.id);
    });
  },
  /**
   * 取消开团
   * 
  */
  removePink:function(e){
    var formId = e.detail.formId,that=this;
    app.baseGet(app.U({ c: 'pink_api', a: 'remove_pink', q: { 
      pink_id: this.data.pinkId,
      cid: this.data.storeCombination.id, 
      formId: formId
    }}),function(res){
      if(res.data.status){
        switch (res.data.status) {
          case '200':
            app.Tips({title:res.data.msg});
            that.getPink();
          break;
        }
      }else{
        return app.Tips({ title: res.msg, icon: 'success' }, { tab: 4, url:'/pages/order_list/index?is_return=1'});
      }
    },function(res){
      return app.Tips({title:res.msg},{tab:3,url:1});
    });
  },

  lookAll:function(){
     this.setData({iShidden: !this.data.iShidden})
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
    if(this.data.isClose) this.getPink();
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({ isClose:1});
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    return {
      title: this.data.userInfo.nickname + '邀请您参团',
      path: '/pages/activity/goods_combination_status/index?id=' + this.data.pinkId,
      imageUrl: this.data.storeCombination.image,
      success: function (){
        return app.Tips({ title: '分享成功',icon: 'success'});
      }
    };
  }
})