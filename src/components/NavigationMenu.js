import React, { useEffect, useState, useRef } from "react";
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
  aeroSvg,
  refreshIconLarge,
  tickIconLarge
} from "./Icons";
import { processMedia, processSync, sleep } from "./functions";

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
const logModalStyles = {
  content: {
    top: '50%',
    left: '50%',
    right: 'auto',
    bottom: 'auto',
    marginRight: '-50%',
    transform: 'translate(-50%, -50%)',
    maxWidth: '750px',
    padding: '30px'
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
  const [openLogModal, setOpenLogModal] = useState(false);
  const [syncLog, setSyncLog] = useState(false);
  const [openMediaModal, setOpenMediaModal] = useState(false);
  const [openSyncRecordModal, setOpenSyncRecordModal] = useState(false);
  const [currentMedia, setCurrentMedia] = useState(null);
  const [totalMedia, setTotalMedia] = useState(null);
  const [isSyncDone, setIsSyncDone] = useState(false);
  const [aeropageModal, setAeropageModal] = useState(true);
  const [currentPosts, setCurrentPosts] = useState(false);
  const [totalPosts, setTotalPosts] = useState(false);
  // const [isMediaCancelled, setIsMediaCancelled] = useState(false);
  let isMediaCancelled = useRef(0);
  // console.log("PLUGIN NAME: ", MYSCRIPT.plugin_name);
  useEffect(() => {
    console.log("use effect");
    listAeropagePages();
  }, []);

  useEffect(() => {
    console.log(response);
  }, [response]);

  useEffect(() => {
    // console.log(searchParams.get("path"));
    setPath(searchParams.get("path"));
    setEditID(searchParams.get("id"));
  }, [url]);

  useEffect(() => {
    // console.log("path status:" + path);
  }, [path]);

  const listAeropagePages = () => {
    var params = new URLSearchParams();
    //
    params.append("action", "aeropageList");
    params.append("title", "test");
    params.append("_ajax_nonce", MYSCRIPT.wp_nonce); 

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
    // console.log("id: " + id);
    // console.log(MYSCRIPT.ajaxUrl);

    // let params = new URLSearchParams();
    // params.append("action", "aeropageSyncPosts");
    // params.append("id", id);

    // axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
    //   console.log("RESPONSE DATA: ", responseAP.data);
    // });
  };

  const handleRefresh = async (id, token) => {
    // console.log("id: " + id);
    // console.log(MYSCRIPT.ajaxUrl);
    setIsSyncDone(false);
    setOpenSyncRecordModal(true);
    //Process the sync by batch
    //Response data is a set of media files.
    const responseData = await processSync({
      token,
      postID: id,
      setOpenSyncRecordModal,
      setTotalPosts,
      setCurrentPosts,
      setResponse,
      response
    });

    // ------------------- THIS IS NO LONGER USED ---------------//
    // let params = new URLSearchParams();
    // params.append("action", "aeropageSyncPosts");
    // params.append("id", id);

    // const responseData = await axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
    //   if(response){
    //     const b = [...response];
    //     const a = b?.find(re => re.ID === id);

    //     if(a){
    //       a.sync_message = responseAP?.data?.message;
    //     }

    //     setResponse(b);
    //   }
    //   return responseAP?.data;
    // })
    //   .catch(err => {
    //     console.log(err);
    //     return null;
    //   });
    // --------------------------------------------------------//
    setIsSyncDone(true);
    await sleep(750);
    setOpenSyncRecordModal(false);

    await processMedia({
      responseData,
      setOpenMediaModal,
      setTotalMedia,
      isMediaCancelled,
      setCurrentMedia
    })

    return responseData;
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
    params.append("_ajax_nonce", MYSCRIPT.wp_nonce);
    
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
                  justifyContent: "space-between",
                  alignItems: "center"
                }}
              >
                <Header toolType={"My Posts"}></Header>
                <div>
                  <a 
                    href="https://builder.aeropage.io/"
                    target={"_blank"}
                    style={{
                      textDecoration: "none"
                    }}
                  >
                    <button
                      className={"btn"}
                      style={{
                        background: "rgb(37, 37, 37)",
                        textDecoration: "none"
                      }}
                    >Go to Aeropage</button>
                  </a>
                </div>
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
                        setOpenLogModal={setOpenLogModal}
                        setSyncLog={setSyncLog}
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
          <Modal
            isOpen={openLogModal}
            style={logModalStyles}
          >
            <div
              style={{
                display: "flex",
                justifyContent: "space-between",
                alignItems: "center",
                gap: "20px"
              }}
            >
              <h2>View Recent Sync Log</h2>
              <div 
                style={{ 
                  cursor: "pointer", 
                  fontSize: "15px"
                }}
                onClick={() => setOpenLogModal(false)}
              >X</div>
            </div>
            <div 
              style={{
                "maxHeight": "500px",
                "height": "auto",
                "overflowY": "auto"
              }}
              dangerouslySetInnerHTML={{
                __html: syncLog ? syncLog : "<h4>No logs found.<h4>"
              }}
            />
          </Modal>
        </>
      );
    } else if (path === "addPost") {
      return <AddPost 
        resetView={resetView}
        setOpenMediaModal={setOpenMediaModal}
        setTotalMedia={setTotalMedia}
        isMediaCancelled={isMediaCancelled}
        setCurrentMedia={setCurrentMedia}
        setOpenSyncRecordModal={setOpenSyncRecordModal} 
        setIsSyncDone={setIsSyncDone}
        handleRefresh={handleRefresh}
      />;
    } else if (path === "editPost") {
      console.log(response);
      return (
        <EditPost
          id={editID}
          editTitle={response?.[idx]?.post_title}
          url={response?.[idx]?.post_name}
          editDynamic={response?.[idx]?.post_excerpt}
          posts={response}
          setOpenMediaModal={setOpenMediaModal}
          setTotalMedia={setTotalMedia}
          isMediaCancelled={isMediaCancelled}
          setCurrentMedia={setCurrentMedia}
          setOpenSyncRecordModal={setOpenSyncRecordModal}
          setIsSyncDone={setIsSyncDone}
          handleRefresh={handleRefresh}
        />
      );
    }
    
  };

  return (
    <>
      { conditionalRender() }
      <Modal
        isOpen={openSyncRecordModal}
        style={customStyles}
      >
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            gap: "20px",
            flexDirection: "column",
            width: "250px"
          }}
        >
          <h2>{isSyncDone ? "Done Syncing Records" :"Syncing Records"}</h2>
          <div
            id="refresh"
            className={isSyncDone ? "" : "refresh-start"}
            style={{
              display: "flex",
              justifyContent: "center",
              alignItems: "center",
              padding: "7px",
              height: "70px",
              width: "70px",
            }}
          >
            {isSyncDone ? tickIconLarge : refreshIconLarge}
          </div>
          { isSyncDone ? 
            <>
              <p
              style={{ 
                fontSize: "13px",
                textDecoration: "underline",
                margin: "0",
                cursor: "pointer"
              }}
              onClick={() => {
                // setIsMediaCancelled(true)
                setOpenSyncRecordModal(false);
              }}
            >Close</p>
          </>: 
            <div>
            <p
              style={{ 
                fontSize: "13px",
                //textDecoration: "underline",
                margin: "0",
                //cursor: "pointer"
              }}
            >Please wait, this can take a while... </p>
            <div>
            {
              totalPosts && (<p
                style={{ 
                  fontSize: "20px",
                  margin: "0",
                  textAlign: "center"
                }}
              >{ currentPosts } / { totalPosts }</p>)
            }
          </div>
            </div>
          }
          <div></div>
        </div>
      </Modal>
      <Modal
        isOpen={openMediaModal}
        style={customStyles}
      >
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "center",
            gap: "20px",
            flexDirection: "column",
            width: "250px"
          }}
        >
          <h2>{ currentMedia?.index === totalMedia ? "Media Downloaded" : "Downloading Media" }</h2>
          <div
            id="refresh"
            className={currentMedia?.index === totalMedia ? "" : "refresh-start" }
            style={{
              display: "flex",
              justifyContent: "center",
              alignItems: "center",
              padding: "7px",
              height: "70px",
              width: "70px",
            }}
          >
            { currentMedia?.index === totalMedia ? tickIconLarge : refreshIconLarge}
          </div>
          <div>
            <p
              style={{ 
                fontSize: "20px",
                margin: "0"
              }}
            >{ currentMedia?.index ?? 1 } / { totalMedia }</p>
          </div>
          <div>
            <p
              style={{ 
                fontSize: "13px",
                textDecoration: "underline",
                margin: "0",
                cursor: "pointer"
              }}
              onClick={() => {
                // setIsMediaCancelled(true)
                isMediaCancelled.current = 1;
                setOpenMediaModal(false)
              }}
            >{currentMedia?.index === totalMedia ? "Close" : "Cancel"}</p>
          </div>
        </div>
      </Modal>
      {
        aeropageModal && (
          <div
            style={{
              position: "fixed",
              bottom: "20px",
              right: "20px",
              maxWidth: "450px"
            }}
          >
            <div
              style={{
                padding: "12px",
                width: 'fit-content',
                background: "#fff",
                display: "flex",
                flexDirection: "column",
                gap: "20px"
              }}
            >
              <a
                  href="https://aeropage.io/"
                  target="_blank"
                >
                <img 
                  src={`${MYSCRIPT.plugin_assets}/aeropage_builder.png`}
                  style={{ maxWidth: "450px" }}
                />
              </a>
              <div
                style={{
                  display: "flex",
                  flexDirection: "row",
                  justifyContent: "space-between",
                  alignItems: "center"
                }}
              >
                <div 
                  style={{ cursor: "pointer" }}
                  onClick={() => {
                    setAeropageModal(false);
                  }}
                >Dismiss</div>
                <a
                  href="https://aeropage.io/"
                  target="_blank"
                >
                  <div 
                    className="btn"
                  >Start Now</div>
                </a>
              </div>
            </div>
          </div>
        )

      }
    </>
  );
};

export default Dashboard;