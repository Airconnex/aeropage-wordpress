import React from "react";
import NavigationMenu from "./components/NavigationMenu.js";
import AddPost from "./components/AddPost.js";

import {
  BrowserRouter as Router,
  Switch,
  Route,
  Link,
  BrowserRouter,
  Routes,
} from "react-router-dom";

const App = () => { 
  return (
    <Router>
      <div>
        {/* <NavigationMenu /> */}

        <div className="App">
          <Routes>
            {/* <Route
              exact
              path="/wordpress/wp-admin/admin.php?page=aeroplugin?&edit=null"
              element={<AddPost />}
            /> */}
            <Route exact path="/*" element={<NavigationMenu />} />

            {/* <Route path="*" element={<NotFound />} /> */}
          </Routes>
        </div>
      </div>
    </Router>
  );
};

export default App;
