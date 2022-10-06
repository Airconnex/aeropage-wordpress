import React, { useEffect, useState } from "react";
import Header from "./header";
import axios from "axios";
import {
  BrowserRouter as Router,
  Switch,
  Route,
  Link,
  BrowserRouter,
  Routes,
} from "react-router-dom";
import { useSearchParams } from "react-router-dom";
import AddPost from "./AddPost";
import EditPost from "./EditPost";
import {
  aeroSvg,
  tickIcon,
  trashIcon,
  refreshIcon,
  settingsIcon,
  warningIcon,
} from "./Icons";

const Dashboard = () => {
  const [response, setResponse] = useState([]);
  const [url, setUrl] = useState(true);
  const [path, setPath] = useState(null);
  const [editID, setEditID] = useState(null);
  const [idx, setIdx] = useState(null);
  const [refreshState, setRefreshState] = useState(false);

  let [searchParams, setSearchParams] = useSearchParams();
  const link = `${MYSCRIPT.plugin_admin_path}admin.php?page=aeroplugin&path=editPost`;

  useEffect(() => {
    console.log("use effect");
    var params = new URLSearchParams();
    //
    params.append("action", "aeropageList");
    params.append("title", "test");
    axios.post(MYSCRIPT.ajaxUrl, params).then(function (response) {
      // console.log(response.data);
      // let newString = response.data.slice(0, -1);
      // let json = JSON.parse(newString);
      setResponse(response?.data);
    });
  }, []);

  useEffect(() => {
    console.log(response);
  }, [response]);

  useEffect(() => {
    console.log(searchParams.get("path"));
    setPath(searchParams.get("path"));
  }, [url]);

  useEffect(() => {
    console.log("path status:" + path);
  }, [path]);

  useEffect(() => {
    console.log("refresh status:" + refreshState);
  }, [refreshState]);

  const resetView = () => {
    setPath(null);
  };

  const handleClick = (id) => {
    console.log("id: " + id);
    console.log(MYSCRIPT.ajaxUrl);

    var params = new URLSearchParams();
    params.append("action", "aeropageSyncPosts");
    params.append("id", id);

    axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
      console.log(responseAP.data);
    });
  };

  const handleRefresh = (id) => {
    console.log("id: " + id);
    console.log(MYSCRIPT.ajaxUrl);

    var params = new URLSearchParams();
    params.append("action", "aeropageSyncPosts");
    params.append("id", id);

    axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
      setRefreshState(false);
      console.log(responseAP.data);
    });
  };

  function refreshContent() {
    let refreshIcon = document.getElementById("refresh-icon");
    let refreshButton = document.getElementById("refresh-button");
    refreshButton.removeAttribute("class");
    refreshButton.disabled = true;

    setTimeout(function () {
      refreshIcon.addEventListener("animationiteration", function () {
        refreshButton.setAttribute("class", "refresh-end");
        refreshButton.disabled = false;
        refreshIcon.removeEventListener("animationiteration");
      });
    }, 100);
  }

  const conditionalRender = () => {
    if (path === null) {
      return (
        <div
          style={{ background: "white", minHeight: "800px", height: "80vh" }}
        >
          <div style={{ display: "flex", justifyContent: "center" }}>
            <div
              style={{
                borderBottom: "1px solid lightGray",
                display: "flex",
                flexDirection: "row",
                paddingLeft: "15px",
                paddingRight: "15px",
                width: "100%",
              }}
            >
              {/* <div
            style={{
              display: "flex",
              alignItems: "center",
              paddingTop: "10px",
              paddingBottom: "10px",
            }}
          >
            <Link to="/">{aeroSvg}</Link>
          </div> */}
              <Header toolType={"Aeropage Plugin"}></Header>
            </div>
          </div>

          <div
            style={{
              width: "100%",
              display: "flex",
              // flexWrap: "wrap",
              flexDirection: "column",
              justifyContent: "center",
              alignItems: "center",
            }}
          >
            <div
              style={{
                width: "75%",
                display: "flex",
                marginTop: "25px",
                paddingLeft: "100px",
                paddingRight: "100px",
                // flexWrap: "wrap",
                flexDirection: "row",
                alignItems: "center",
              }}
            >
              <p
                style={{
                  color: "#595B5C",
                  width: "100%",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "600",
                  fontSize: "14px",
                  lineHeight: "120%",
                }}
              >
                Custom Post Syncronizer
              </p>

              <div
                style={{
                  width: "100%",
                  display: "flex",
                  // flexWrap: "wrap",
                  justifyContent: "right",
                  alignItems: "center",
                }}
              >
                <Link
                  to={`${MYSCRIPT.plugin_admin_path}admin.php?page=aeroplugin&path=addPost`}
                >
                  <button
                    onClick={() => setUrl(!url)}
                    style={{
                      width: "100px",
                      fontFamily: "'Inter', sans-serif",
                      fontStyle: "normal",
                      fontWeight: "500",
                      fontSize: "12px",
                      lineHeight: "24px",
                      background: "#633CE3",
                      cursor: "pointer",
                      color: "white",
                      padding: "8px 13px 8px 13px",
                      border: "none",
                      borderRadius: "6px",
                    }}
                  >
                    Add a Post
                  </button>
                </Link>
              </div>
            </div>

            <div
              style={{
                display: "flex",
                flexDirection: "row",
                justifyContent: "center",
                flexWrap: "wrap",
                maxWidth: "80%",
              }}
            >
              {response.map((el, idx) => {
                return (
                  <>
                    <div
                      style={{
                        border: "1px solid #B9B9B9",
                        padding: "10px",
                        minWidth: "150px",
                        maxWidth: "250px",
                        display: "flex",
                        flexDirection: "column",
                        boxShadow: "0px 4px 4px 0px #00000040",
                        flex: "1 1 200px",
                        margin: "10px 10px 10px 10px",
                        borderRadius: "8px",
                      }}
                    >
                      <Link
                        className="link"
                        onClick={() => {
                          setUrl(!url);
                          setEditID(el.ID);
                          setIdx(idx);
                        }}
                        style={{
                          textDecoration: "none",
                          color: "black",
                        }}
                        to={`${link}&id=${el.ID}`}
                      >
                        <div
                          style={{
                            borderBottom: "1px solid #F4F5F8",
                            display: "flex",
                            width: "100%",
                            paddingBottom: "10px",
                          }}
                        >
                          <div
                            style={{
                              display: "flex",
                              flexDirection: "row",
                              width: "100%",
                            }}
                          >
                            <span
                              style={{
                                fontFamily: "'Inter', sans-serif",
                                fontStyle: "normal",
                                fontWeight: "600",
                                whiteSpace: "nowrap",
                                display: "flex",
                                alignItems: "center",
                                width: "fit-content",
                                color: "#595B5C",
                                fontSize: "12px",
                                lineHeight: "16.8px",
                              }}
                            >
                              {el?.post_title}
                            </span>
                            {/* <button onClick={() => handleClick(el?.ID)}>
                        Refresh
                      </button> */}

                            <div
                              style={{
                                display: "flex",
                                width: "100%",
                                justifyContent: "right",
                              }}
                            >
                              <div
                                style={{
                                  display: "flex",
                                  justifyContent: "center",
                                  alignItems: "center",
                                  padding: "7px",
                                  borderRadius: "3px",
                                  background: "#25A6A61A",
                                }}
                                onClick={() => handleClick(el?.ID)}
                              >
                                {tickIcon}
                              </div>
                            </div>
                          </div>
                        </div>
                      </Link>

                      <div
                        style={{
                          height: "100%",
                          display: "flex",
                          // alignItems: "center",
                          flexDirection: "column",
                        }}
                      >
                        <span
                          style={{
                            color: "#595B5C",
                            fontFamily: "'Inter', sans-serif",
                            fontStyle: "normal",
                            fontWeight: "400",
                            fontSize: "10px",
                            height: "100%",
                            display: "flex",
                            alignItems: "center",
                            lineHeight: "17.5px",
                            paddingTop: "10px",
                            paddingBottom: "10px",
                          }}
                        >
                          Updated 12:22pm, 13/03/2022
                        </span>
                        <div
                          style={{
                            width: "100%",
                            height: "100%",
                            display: "flex",
                            justifyContent: "right",
                            alignItems: "end",
                          }}
                        >
                          <div
                            id="trash"
                            style={{
                              display: "flex",
                              justifyContent: "center",
                              alignItems: "center",
                              padding: "7px",
                              height: "28px",
                              width: "28px",
                            }}
                            onClick={() => handleClick(el?.ID)}
                          >
                            {trashIcon}
                          </div>
                          <Link
                            className="link"
                            onClick={() => {
                              setUrl(!url);
                              setEditID(el.ID);
                              setIdx(idx);
                            }}
                            style={{
                              textDecoration: "none",
                              color: "black",
                            }}
                            to={`${link}&id=${el.ID}`}
                          >
                            <div
                              id="settings"
                              style={{
                                display: "flex",
                                justifyContent: "center",
                                alignItems: "center",
                                padding: "7px",
                                height: "28px",
                                width: "28px",
                              }}
                              onClick={() => handleClick(el?.ID)}
                            >
                              {settingsIcon}
                            </div>
                          </Link>
                          <div
                            id="refresh"
                            // className="refresh-start"
                            style={{
                              display: "flex",
                              justifyContent: "center",
                              alignItems: "center",
                              padding: "7px",
                              height: "28px",
                              width: "28px",
                            }}
                            onClick={() => {
                              setRefreshState(true);
                              handleRefresh(el?.ID);
                            }}
                          >
                            {refreshIcon}
                          </div>
                        </div>
                      </div>

                      {/* <p style={{ fontSize: "12px" }}>
                        Create rich html with images and buttons to use in emails.
                        Export to Airtable and send using any automation tools
                      </p> */}
                    </div>{" "}
                  </>
                );
              })}
            </div>
          </div>
          <div
            style={{
              display: "flex",
              flexWrap: "wrap",
              flexDirection: "column",
              justifyContent: "center",
              alignItems: "center",
            }}
          >
            <h2
              style={{
                paddingTop: "15px",
                textAlign: "center",
                paddingLeft: "15px",
                paddingRight: "15px",
              }}
            ></h2>
            <div
              style={{
                display: "flex",
                flexDirection: "row",
                justifyContent: "center",
                flexWrap: "wrap",
                maxWidth: "80%",
              }}
            >
              {/* {tools.map((el) => {
            return (
                <div
                  className="defaultCursor"
                  style={{
                    textDecoration: "none",
                    border: "1px solid lightGray",
                    padding: "10px 10px 10px 10px",
                    maxWidth: "300px",
                    flex: "1 1 200px",
                    margin: "10px 10px 10px 10px",
                    borderRadius: "8px",
                  }}
                >
                  <h4>{el.title}</h4>
                  <p style={{ fontSize: "12px" }}>{el.description}</p>
                </div>
            );
          })} */}
            </div>
          </div>
        </div>
      );
    } else if (path === "addPost") {
      return <AddPost resetView={resetView} />;
    } else if (path === "editPost") {
      return (
        <EditPost
          id={editID}
          editTitle={response[idx].post_title}
          url={response[idx].post_name}
          editDynamic={response[idx].post_excerpt}
        />
      );
    }
  };

  return conditionalRender();
};

export default Dashboard;
