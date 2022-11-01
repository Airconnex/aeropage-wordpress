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
import Card from "./Card";
import Modal from 'react-modal';
import {
  aeroSvg
} from "./Icons";

const customStyles = {
  content: {
    top: '50%',
    left: '50%',
    right: 'auto',
    bottom: 'auto',
    marginRight: '-50%',
    transform: 'translate(-50%, -50%)',
    maxWidth: '350px'
  },
};
Modal.setAppElement('#aeroplugin');

const Dashboard = () => {
  const [response, setResponse] = useState([]);
  const [url, setUrl] = useState(true);
  const [path, setPath] = useState(null);
  const [editID, setEditID] = useState(null);
  const [idx, setIdx] = useState(null);
  const [openModal, setOpenModal] = useState(false);
  const [toBeDeleted, setToBeDeleted] = useState(null);
  const [isLoadingDelete, setIsLoadingDelete] = useState(false);
  const [searchParams, setSearchParams] = useSearchParams();
  
  console.log("PLUGIN NAME: ", MYSCRIPT.plugin_name);
  useEffect(() => {
    console.log("use effect");
    listAeropagePages();
  }, []);

  useEffect(() => {
    console.log(response);
  }, [response]);

  useEffect(() => {
    console.log(searchParams.get("path"));
    setPath(searchParams.get("path"));
    setEditID(searchParams.get("id"));
  }, [url]);

  useEffect(() => {
    console.log("path status:" + path);
  }, [path]);

  const listAeropagePages = () => {
    var params = new URLSearchParams();
    //
    params.append("action", "aeropageList");
    params.append("title", "test");
    axios.post(MYSCRIPT.ajaxUrl, params).then(function (response) {
      // console.log(response.data);
      // let newString = response.data.slice(0, -1);
      // let json = JSON.parse(newString);
      setResponse(response?.data);
      setIsLoadingDelete(false);
      setOpenModal(false);
    });
  }

  const resetView = () => {
    setPath(null);
  };

  const handleClick = (id) => {
    console.log("id: " + id);
    console.log(MYSCRIPT.ajaxUrl);

    let params = new URLSearchParams();
    params.append("action", "aeropageSyncPosts");
    params.append("id", id);

    axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
      console.log("RESPONSE DATA: ", responseAP.data);
    });
  };

  const handleRefresh = async (id) => {
    console.log("id: " + id);
    console.log(MYSCRIPT.ajaxUrl);

    let params = new URLSearchParams();
    params.append("action", "aeropageSyncPosts");
    params.append("id", id);

    return await axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
      return responseAP?.data;
    });
  };

  const deletePost = async () => {
    if(!toBeDeleted) {
      alert("No post ID set.");
      return;
    };

    setIsLoadingDelete(true);

    let params = new URLSearchParams();
    params.append("action", "aeropageDeletePost");
    params.append("id", toBeDeleted);

    return await axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
      console.log("DELETE RESPONSE: ", responseAP?.data);
      // listAeropagePages();
      location.reload();
    })
  }

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
        <>
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
                  width: "78%",
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
                    to={`${MYSCRIPT.plugin_admin_path}admin.php?page=${MYSCRIPT.plugin_name}&path=addPost`}
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
                      <Card
                        el={el}
                        id={idx}
                        setUrl={setUrl}
                        setEditID={setEditID}
                        setIdx={setIdx}
                        handleClick={handleClick}
                        url={url}
                        handleRefresh={handleRefresh}
                        setOpenModal={setOpenModal}
                        setToBeDeleted={setToBeDeleted}
                      />{" "}
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
          <Modal
            isOpen={openModal}
            style={customStyles}
          >
            <h2>Delete</h2>
            <p
              style={{
                marginBottom: "3em"
              }}
            >Are you sure you want to delete this custom post type? This will delete the custom post type, and the posts created by it. </p>
            <div
              style={{
                display: "flex",
                justifyContent: "space-between",
                margin: "1em 0"
              }}
            >
              <button
                onClick={() => setOpenModal(false)}
                style={{
                  background: "transparent",
                  border: "0px",
                  padding: "8px 13px",
                  cursor: "pointer",
                  textDecoration: "underline",
                }}
              >No, cancel.</button>
              <button
                onClick={() => deletePost()}
                style={{
                  background: "#C22525",
                  border: "1px solid #C22525",
                  borderRadius: "6px",
                  padding: "8px 13px",
                  color: "white",
                  cursor: "pointer",
                }}
                disabled={isLoadingDelete}
              >{isLoadingDelete ? "Deleting..." : "Yes, delete."}</button>
            </div>
          </Modal>
        </>
      );
    } else if (path === "addPost") {
      return <AddPost resetView={resetView} />;
    } else if (path === "editPost") {
      console.log(response);
      return (
        <EditPost
          id={editID}
          editTitle={response?.[idx]?.post_title}
          url={response?.[idx]?.post_name}
          editDynamic={response?.[idx]?.post_excerpt}
          posts={response}
        />
      );
    }
  };

  return conditionalRender();
};

export default Dashboard;
