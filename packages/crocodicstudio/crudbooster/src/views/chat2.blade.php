<style>

/* Basic styling for the right sidebar */
.main-sidebar-right {
  position: fixed;
  right: -250px; /* Hide initially */
  top: 0;
  height: 100%;
  width: 250px;
  background-color: #222d32;
  transition: right 0.3s ease;
  z-index: 1000;
}

/* Sidebar open state */
.main-sidebar-right.open {
  right: 0;
}

/* Styling for toggle button */
.toggle-sidebar-btn {
  position: fixed;
  /*right: 10px;
  top: 10px;*/
  z-index: 1100;
  /*background-color: #fff;*/
  border: none;
  cursor: pointer;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 16px;
}

/* Sidebar inner components */
.main-sidebar-right .user-panel {
  padding: 10px;
  color: #fff;
}

.main-sidebar-right .sidebar-menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.main-sidebar-right .sidebar-menu li {
  padding: 10px;
  color: #b8c7ce;
}

.main-sidebar-right .sidebar-menu li.active > a {
  background-color: #1e282c;
  color: #fff;
}

.main-sidebar-right .sidebar-menu a {
  color: #b8c7ce;
  text-decoration: none;
}

</style>



<aside class=" main-sidebar-right">
  <section class="sidebar">
<div class="page-content page-container" id="page-content">
    <div>
        <div style="margin: 0;" class="row  d-flex justify-content-center">

<div  style="padding-left: 0px;padding-right: 0px;">
            <div class="card card-bordered">
              <div class="card-header">
                <h4 class="card-title"><strong>AI Assistance</strong></h4>
                <!--<a class="btn btn-xs btn-secondary" href="#" data-abc="true">Let's Chat App</a>-->
              </div>


              <div class="ps-container ps-theme-default ps-active-y" id="chat-content" style="overflow-y: scroll !important; height:400px !important;">
                <div class="media media-chat">
                  <img class="avatar" src="https://img.icons8.com/color/36/000000/administrator-male.png" alt="...">
                  <div class="media-body">
                    <p>Ciao</p>
                    <p>Sono il tuo assistente AI</p>
                    <p>Come posso aiutarti?</p>
                    <!--<p class="meta"><time datetime="2018">23:58</time></p>-->
                  </div>
                </div>


              <div class="ps-scrollbar-x-rail" style="left: 0px; bottom: 0px;">
                <div class="ps-scrollbar-x" tabindex="0" style="left: 0px; width: 0px;"></div></div><div class="ps-scrollbar-y-rail" style="top: 0px; height: 0px; right: 2px;"><div class="ps-scrollbar-y" tabindex="0" style="top: 0px; height: 2px;"></div></div></div>

              <div class="publisher bt-1 border-light">
                <img class="avatar avatar-xs" src="https://img.icons8.com/color/36/000000/administrator-male.png" alt="...">
                <input class="publisher-input" type="text" placeholder="Scrivi qui">
                <button id="send-btn" class="publisher-btn text-info"  type="button"><i class="fa fa-paper-plane"></i></button>
              </div>

             </div>
          </div>

          </div>
          </div>
          </div>
  </section>

</aside>


